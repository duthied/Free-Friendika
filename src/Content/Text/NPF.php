<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Content\Text;

use DOMDocument;
use DOMElement;
use Friendica\Model\Photo;
use Friendica\Model\Post;

/**
 * Tumblr Neue Post Format
 * @see https://www.tumblr.com/docs/npf
 */
class NPF
{
	static public function fromBBCode(string $bbcode, int $uri_id): array
	{
		$npf = [];

		$bbcode = self::prepareBody($bbcode);

		$html = BBCode::convert($bbcode, false, BBCode::CONNECTORS);
		if (empty($html)) {
			return [];
		}

		$doc = new DOMDocument();
		if (!@$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'))) {
			return [];
		}

		$node = $doc->getElementsByTagName('body')->item(0);
		foreach ($node->childNodes as $child) {
			if ($child->nodeName == '#text') {
				$npf[] = [
					'type' => 'text',
					'text' => $child->textContent,
				];
			} else {
				$npf = self::routeElements($child, $uri_id, $npf);
			}
		}

		return self::addLinkBlock($uri_id, $npf);
	}

	public static function prepareBody(string $body): string
	{
		$shared = BBCode::fetchShareAttributes($body);
		if (!empty($shared)) {
			$body = $shared['shared'];
		}

		$body = BBCode::removeAttachment($body);

		$body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $body);

		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]\s*\[/url\]#ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (preg_match('#/photo/.*-[01]\.#ism', $picture[2]) && (preg_match('#/photo/.*-0\.#ism', $picture[1]) || preg_match('#/photos/.*/image/#ism', $picture[1]))) {
					$body = str_replace($picture[0], "\n\n[img=" . str_replace('-1.', '-0.', $picture[2]) . "]" . $picture[3] . "[/img]\n\n", $body);
				}
			}
		}

		$body = preg_replace("/\[img\=(.*?)\](.*?)\[\/img\]/ism", "\n\n[img=$1]$2[/img]\n\n", $body);

		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img\]([^\[]+?)\[/img\]\s*\[/url\]#ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (preg_match('#/photo/.*-[01]\.#ism', $picture[2]) && (preg_match('#/photo/.*-0\.#ism', $picture[1]) || preg_match('#/photos/.*/image/#ism', $picture[1]))) {
					$body = str_replace($picture[0], "\n\n[img]" . str_replace('-1.', '-0.', $picture[2]) . "[/img]\n\n", $body);
				}
			}
		}

		$body = preg_replace("/\[img\](.*?)\[\/img\]/ism", "\n\n[img]$1[/img]\n\n", $body);
		$body = preg_replace("/\[audio\](.*?)\[\/audio\]/ism", "\n\n[audio]$1[/audio]\n\n", $body);
		$body = preg_replace("/\[video\](.*?)\[\/video\]/ism", "\n\n[video]$1[/video]\n\n", $body);

		do {
			$oldbody = $body;
			$body = str_replace(["\n\n\n"], ["\n\n"], $body);
		} while ($oldbody != $body);

		return trim($body);
	}

	static private function routeElements(DOMElement $child, int $uri_id, array $npf): array
	{
		switch ($child->nodeName) {
			case 'blockquote':
				$npf = self::addTextBlock($child, $uri_id, $npf, 'indented');
				break;

			case 'h1':
				$npf = self::addTextBlock($child, $uri_id, $npf, 'heading1');
				break;

			case 'h2':
				$npf = self::addTextBlock($child, $uri_id, $npf, 'heading1');
				break;

			case 'h3':
				$npf = self::addTextBlock($child, $uri_id, $npf, 'heading1');
				break;

			case 'h4':
				$npf = self::addTextBlock($child, $uri_id, $npf, 'heading2');
				break;

			case 'h5':
				$npf = self::addTextBlock($child, $uri_id, $npf, 'heading2');
				break;

			case 'h6':
				$npf = self::addTextBlock($child, $uri_id, $npf, 'heading2');
				break;

			case 'ul':
				$npf = self::addListBlock($child, $uri_id, $npf, false, 0);
				break;

			case 'ol':
				$npf = self::addListBlock($child, $uri_id, $npf, true, 0);
				break;

			case 'hr':
			case 'br':
				break;

			case 'pre':
			case 'code':
				$npf = self::addTextBlock($child, $uri_id, $npf, 'indented');
				break;

			case 'a':
				$npf = self::addMediaBlock($child, $uri_id, $npf);
				break;

			case 'table':
				// Unsupported
				// $child->ownerDocument->saveHTML($child)
				break;

			case 'img':
				$npf = self::addImageBlock($child, $uri_id, $npf);
				break;

			default:
				$npf = self::addTextBlock($child, $uri_id, $npf);
				break;
		}
		return $npf;
	}

	static private function addImageBlock(DOMElement $child, int $uri_id, array $npf): array
	{
		$attributes = [];
		foreach ($child->attributes as $key => $attribute) {
			$attributes[$key] = $attribute->value;
		}
		if (empty($attributes['src'])) {
			return $npf;
		}

		$entry = [
			'type'  => 'image',
			'media' => [],
		];

		if (!empty($attributes['alt'])) {
			$entry['alt_text'] = $attributes['alt'];
		}

		if (!empty($attributes['title']) && ($attributes['alt'] ?? '' != $attributes['title'])) {
			$entry['caption'] = $attributes['title'];
		}

		$rid = Photo::ridFromURI($attributes['src']);
		if (!empty($rid)) {
			$photos = Photo::selectToArray([], ['resource-id' => $rid]);
			foreach ($photos as $photo) {
				$entry['media'][] = [
					'type'   => $photo['type'],
					'url'    => str_replace('-0.', '-' . $photo['scale'] . '.', $attributes['src']),
					'width'  => $photo['width'],
					'height' => $photo['height'],
				];
			}
			if (empty($attributes['alt']) && !empty($photos[0]['desc'])) {
				$entry['alt_text'] = $photos[0]['desc'];
			}
		} elseif ($media = Post\Media::getByURL($uri_id, $attributes['src'], [Post\Media::IMAGE])) {
			$entry['media'][] = [
				'type'   => $media['mimetype'],
				'url'    => $media['url'],
				'width'  => $media['width'],
				'height' => $media['height'],
			];
			if (empty($attributes['alt']) && !empty($media['description'])) {
				$entry['alt_text'] = $media['description'];
			}
		} else {
			$entry['media'][] = ['url' => $attributes['src']];
		}

		$npf[] = $entry;

		return $npf;
	}

	static private function addMediaBlock(DOMElement $child, int $uri_id, array $npf): array
	{
		$attributes = [];
		foreach ($child->attributes as $key => $attribute) {
			$attributes[$key] = $attribute->value;
		}
		if (empty($attributes['href'])) {
			return $npf;
		}

		$media = Post\Media::getByURL($uri_id, $attributes['href'], [Post\Media::AUDIO, Post\Media::VIDEO]);
		if (!empty($media)) {
			switch ($media['type']) {
				case Post\Media::AUDIO:
					$entry = [
						'type' => 'audio',
						'media' => [
							'type' => $media['mimetype'],
							'url'  => $media['url'],
						]
					];

					if (!empty($media['name'])) {
						$entry['title'] = $media['name'];
					} elseif (!empty($media['description'])) {
						$entry['title'] = $media['description'];
					}

					$npf[] = self::addPoster($media, $entry);
					break;

				case Post\Media::VIDEO:
					$entry = [
						'type' => 'video',
						'media' => [
							'type' => $media['mimetype'],
							'url'  => $media['url'],
						]
					];

					$npf[] = self::addPoster($media, $entry);
					break;
			}
		} else {
			$npf[] = [
				'type' => 'text',
				'text' => $child->textContent,
				'formatting' => [
					'start' => 0,
					'end'   => strlen($child->textContent),
					'type'  => 'link',
					'url'   => $attributes['href']
				]
			];
		}
		return $npf;
	}

	static private function addPoster(array $media, array $entry): array
	{
		$poster = [];
		if (!empty($media['preview'])) {
			$poster['url'] = $media['preview'];
		}
		if (!empty($media['preview-width'])) {
			$poster['width'] = $media['preview-width'];
		}
		if (!empty($media['preview-height'])) {
			$poster['height'] = $media['preview-height'];
		}
		if (!empty($poster)) {
			$entry['poster'] = $poster;
		}
		return $entry;
	}

	static private function getTypeForNodeName(string $nodename): string
	{
		switch ($nodename) {
			case 'b':
			case 'strong':
				return 'bold';

			case 'i':
			case 'em':
				return 'italic';

			case 's':
				return 'strikethrough';
		}
		return '';
	}

	static private function fetchText(DOMElement $child, array $text = ['text' => '', 'formatting' => []]): array
	{
		foreach ($child->childNodes as $node) {
			$start = strlen($text['text']);

			$type = self::getTypeForNodeName($node->nodeName);

			if ($node->nodeName == 'br') {
				$text['text'] .= "\n";
			} elseif (($type != '') || in_array($node->nodeName, ['#text', 'code', 'a', 'p', 'span', 'u', 'img', 'summary', 'ul', 'blockquote', 'h3', 'ol'])) {
				$text['text'] .= $node->textContent;
			} else {
				echo $child->ownerDocument->saveHTML($child) . "\n";
				die($node->nodeName . "\n");
			}
			if (!empty($type)) {
				$text['formatting'][] = ['start' => $start, 'end' => strlen($text['text']), 'type' => $type];
			}
		}
		return $text;
	}

	static private function addTextBlock(DOMElement $child, int $uri_id, array $npf, string $subtype = ''): array
	{
		if (empty($subtype) && ($child->textContent == $child->firstChild->textContent) && ($child->firstChild->nodeName != '#text')) {
			return self::routeElements($child->firstChild, $uri_id, $npf);
		}

		$element = ['type' => 'text'];

		if (!empty($subtype)) {
			$element['subtype'] = $subtype;
		}

		$text = self::fetchText($child);

		$element['text']       = $text['text'];
		$element['formatting'] = $text['formatting'];

		if (empty($subtype)) {
			$type = self::getTypeForNodeName($child->nodeName);
			if (!empty($type)) {
				$element['formatting'][] = ['start' => 0, 'end' => strlen($element['text']), 'type' => $type];
			}
		}

		if (empty($element['formatting'])) {
			unset($element['formatting']);
		}

		$npf[] = $element;

		return $npf;
	}

	static private function addListBlock(DOMElement $child, int $uri_id, array $npf, bool $ordered, int $level): array
	{
		foreach ($child->childNodes as $node) {
			switch ($node->nodeName) {
				case 'ul':
					$npf = self::addListBlock($node, $uri_id, $npf, false, $level++);
				case 'ol':
					$npf = self::addListBlock($node, $uri_id, $npf, true, $level++);
				case 'li':
					$text = self::fetchText($node);

					$entry = [
						'type'    => 'text',
						'subtype' => $ordered ? 'ordered-list-item' : 'unordered-list-item',
						'text'    => $text['text']
					];
					if ($level > 0) {
						$entry['indent_level'] = $level;
					}
					if (!empty($text['formatting'])) {
						$entry['formatting'] = $text['formatting'];
					}
					$npf[] = $entry;
			}
		}

		return $npf;
	}

	static private function addLinkBlock(int $uri_id, array $npf): array
	{
		foreach (Post\Media::getByURIId($uri_id, [Post\Media::HTML]) as $link) {
			$host = parse_url($link['url'], PHP_URL_HOST);
			if (in_array($host, ['www.youtube.com', 'youtu.be'])) {
				$entry = [
					'type'     => 'video',
					'provider' => 'youtube',
					'url'      => $link['url'],
				];
			} elseif (in_array($host, ['vimeo.com'])) {
				$entry = [
					'type'     => 'video',
					'provider' => 'vimeo',
					'url'      => $link['url'],
				];
			} elseif (in_array($host, ['open.spotify.com'])) {
				$entry = [
					'type'     => 'audio',
					'provider' => 'spotify',
					'url'      => $link['url'],
				];
			} else {
				$entry = [
					'type' => 'link',
					'url'  => $link['url'],
				];
				if (!empty($link['name'])) {
					$entry['title'] = $link['name'];
				}
				if (!empty($link['description'])) {
					$entry['description'] = $link['description'];
				}
				if (!empty($link['author-name'])) {
					$entry['author'] = $link['author-name'];
				}
				if (!empty($link['publisher-name'])) {
					$entry['site_name'] = $link['publisher-name'];
				}
			}

			$npf[] = self::addPoster($link, $entry);
		}
		return $npf;
	}
}
