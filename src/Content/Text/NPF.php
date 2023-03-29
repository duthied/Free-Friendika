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

		$element = $doc->getElementsByTagName('body')->item(0);
		$npf = self::routeChildren($element, $uri_id, 0, $npf);

		return self::addLinkBlock($uri_id, 0, $npf);
	}

	static private function prepareBody(string $body): string
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

	static private function routeChildren(DOMElement $element, int $uri_id, int $level, array $npf): array
	{
		$text       = '';
		$formatting = [];

		foreach ($element->childNodes as $child) {
			switch ($child->nodeName) {
				case 'blockquote':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addQuoteBlock($child, $uri_id, $level, $npf);
					break;
	
				case 'h1':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addTextBlock($child, $uri_id, $level, $npf, 'heading1');
					break;
	
				case 'h2':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addTextBlock($child, $uri_id, $level, $npf, 'heading1');
					break;
	
				case 'h3':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addTextBlock($child, $uri_id, $level, $npf, 'heading1');
					break;
	
				case 'h4':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addTextBlock($child, $uri_id, $level, $npf, 'heading2');
					break;
	
				case 'h5':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addTextBlock($child, $uri_id, $level, $npf, 'heading2');
					break;
	
				case 'h6':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addTextBlock($child, $uri_id, $level, $npf, 'heading2');
					break;
	
				case 'ul':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addListBlock($child, $uri_id, $level, $npf, false, 0);
					break;
	
				case 'ol':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addListBlock($child, $uri_id, $level, $npf, true, 0);
					break;
	
				case 'hr':
				case 'br':
					$text .= "\n";
					break;
	
				case 'pre':
				case 'code':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addTextBlock($child, $uri_id, $level, $npf, 'indented');
					break;
	
				case 'a':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addMediaBlock($child, $uri_id, $level, $npf);
					break;
	
				case 'table':
					// Unsupported
					// $child->ownerDocument->saveHTML($child)
					break;
	
				case 'img':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addImageBlock($child, $uri_id, $level, $npf);
					break;

				case 'p':
				case 'div':
					$npf = self::addText($text, $formatting, $npf);
					$npf = self::addTextBlock($child, $uri_id, $level, $npf);
					break;

				default:
					$text .= $child->textContent;
					break;
			}
		}
		return $npf;
	}

	static private function addText(string $text, array $formatting, array $npf): array
	{
		if (empty($text)) {
			return $npf;
		}
		$block = [
			'type' => 'text',
			'text' => $text,
		];

		if (!empty($formatting)) {
			$block['formatting'] = $formatting;
		}

		$npf[] = $block;

		return $npf;
	}

	static private function routeElement(DOMElement $element, int $uri_id, int $level, array $npf): array
	{
		switch ($element->nodeName) {
			case 'blockquote':
				$npf = self::addQuoteBlock($element, $uri_id, $level, $npf);
				break;

			case 'h1':
				$npf = self::addTextBlock($element, $uri_id, $level, $npf, 'heading1');
				break;

			case 'h2':
				$npf = self::addTextBlock($element, $uri_id, $level, $npf, 'heading1');
				break;

			case 'h3':
				$npf = self::addTextBlock($element, $uri_id, $level, $npf, 'heading1');
				break;

			case 'h4':
				$npf = self::addTextBlock($element, $uri_id, $level, $npf, 'heading2');
				break;

			case 'h5':
				$npf = self::addTextBlock($element, $uri_id, $level, $npf, 'heading2');
				break;

			case 'h6':
				$npf = self::addTextBlock($element, $uri_id, $level, $npf, 'heading2');
				break;

			case 'ul':
				$npf = self::addListBlock($element, $uri_id, $level, $npf, false, 0);
				break;

			case 'ol':
				$npf = self::addListBlock($element, $uri_id, $level, $npf, true, 0);
				break;

			case 'hr':
			case 'br':
				break;

			case 'pre':
			case 'code':
				$npf = self::addTextBlock($element, $uri_id, $level, $npf, 'indented');
				break;

			case 'a':
				$npf = self::addMediaBlock($element, $uri_id, $level, $npf);
				break;

			case 'table':
				// Unsupported
				// $element->ownerDocument->saveHTML($element)
				break;

			case 'img':
				$npf = self::addImageBlock($element, $uri_id, $level, $npf);
				break;

			default:
				$npf = self::addTextBlock($element, $uri_id, $level, $npf);
				break;
		}
		return $npf;
	}

	static private function addImageBlock(DOMElement $element, int $uri_id, int $level, array $npf): array
	{
		$attributes = [];
		foreach ($element->attributes as $key => $attribute) {
			$attributes[$key] = $attribute->value;
		}
		if (empty($attributes['src'])) {
			return $npf;
		}

		$block = [
			'type'  => 'image',
			'media' => [],
		];

		if (!empty($attributes['alt'])) {
			$block['alt_text'] = $attributes['alt'];
		}

		if (!empty($attributes['title']) && ($attributes['alt'] ?? '' != $attributes['title'])) {
			$block['caption'] = $attributes['title'];
		}

		$rid = Photo::ridFromURI($attributes['src']);
		if (!empty($rid)) {
			$photos = Photo::selectToArray([], ['resource-id' => $rid]);
			foreach ($photos as $photo) {
				$block['media'][] = [
					'type'   => $photo['type'],
					'url'    => str_replace('-0.', '-' . $photo['scale'] . '.', $attributes['src']),
					'width'  => $photo['width'],
					'height' => $photo['height'],
				];
			}
			if (empty($attributes['alt']) && !empty($photos[0]['desc'])) {
				$block['alt_text'] = $photos[0]['desc'];
			}
		} elseif ($media = Post\Media::getByURL($uri_id, $attributes['src'], [Post\Media::IMAGE])) {
			$block['media'][] = [
				'type'   => $media['mimetype'],
				'url'    => $media['url'],
				'width'  => $media['width'],
				'height' => $media['height'],
			];
			if (empty($attributes['alt']) && !empty($media['description'])) {
				$block['alt_text'] = $media['description'];
			}
		} else {
			$block['media'][] = ['url' => $attributes['src']];
		}

		if ($level > 0) {
			$block['indent_level'] = $level;
		}

		$npf[] = $block;

		return $npf;
	}

	static private function addMediaBlock(DOMElement $element, int $uri_id, int $level, array $npf): array
	{
		$attributes = [];
		foreach ($element->attributes as $key => $attribute) {
			$attributes[$key] = $attribute->value;
		}
		if (empty($attributes['href'])) {
			return $npf;
		}

		$media = Post\Media::getByURL($uri_id, $attributes['href'], [Post\Media::AUDIO, Post\Media::VIDEO]);
		if (!empty($media)) {
			switch ($media['type']) {
				case Post\Media::AUDIO:
					$block = [
						'type' => 'audio',
						'media' => [
							'type' => $media['mimetype'],
							'url'  => $media['url'],
						]
					];

					if (!empty($media['name'])) {
						$block['title'] = $media['name'];
					} elseif (!empty($media['description'])) {
						$block['title'] = $media['description'];
					}

					$block = self::addPoster($media, $block);
					break;

				case Post\Media::VIDEO:
					$block = [
						'type' => 'video',
						'media' => [
							'type' => $media['mimetype'],
							'url'  => $media['url'],
						]
					];

					$block = self::addPoster($media, $block);
					break;
			}
		} else {
			$block = [
				'type' => 'text',
				'text' => $element->textContent,
				'formatting' => [
					'start' => 0,
					'end'   => strlen($element->textContent),
					'type'  => 'link',
					'url'   => $attributes['href']
				]
			];
		}

		if ($level > 0) {
			$block['indent_level'] = $level;
		}

		$npf[] = $block;

		return $npf;
	}

	static private function addPoster(array $media, array $block): array
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
			$block['poster'] = $poster;
		}
		return $block;
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

	static private function fetchText(DOMElement $element, array $text = ['text' => '', 'formatting' => []]): array
	{
		foreach ($element->childNodes as $child) {
			$start = strlen($text['text']);

			$type = self::getTypeForNodeName($child->nodeName);

			if ($child->nodeName == 'br') {
				$text['text'] .= "\n";
			} elseif (($type != '') || in_array($child->nodeName, ['#text', 'code', 'a', 'p', 'span', 'u', 'img', 'summary', 'ul', 'blockquote', 'h3', 'ol'])) {
				$text['text'] .= $child->textContent;
			} else {
				echo $element->ownerDocument->saveHTML($element) . "\n";
				die($child->nodeName . "\n");
			}
			if (!empty($type)) {
				$text['formatting'][] = ['start' => $start, 'end' => strlen($text['text']), 'type' => $type];
			}
		}
		return $text;
	}

	static private function addQuoteBlock(DOMElement $element, int $uri_id, int $level, array $npf): array
	{
		$block = ['type' => 'text', 'subtype' => 'indented'];

		if ($level > 0) {
			$block['indent_level'] = $level;
		}

		$npf[] = $block;

		$npf = self::routeChildren($element, $uri_id, 0, $npf);

		return $npf;
	}

	static private function addTextBlock(DOMElement $element, int $uri_id, int $level, array $npf, string $subtype = ''): array
	{
		if (empty($subtype) && ($element->textContent == $element->firstChild->textContent) && ($element->firstChild->nodeName != '#text')) {
			return self::routeElement($element->firstChild, $uri_id, $level, $npf);
		}

		$block = ['type' => 'text'];

		if (!empty($subtype)) {
			$block['subtype'] = $subtype;
		}

		$text = self::fetchText($element);

		$block['text']       = $text['text'];
		$block['formatting'] = $text['formatting'];

		if (empty($subtype)) {
			$type = self::getTypeForNodeName($element->nodeName);
			if (!empty($type)) {
				$block['formatting'][] = ['start' => 0, 'end' => strlen($block['text']), 'type' => $type];
			}
		}

		if (empty($block['formatting'])) {
			unset($block['formatting']);
		}

		if ($level > 0) {
			$block['indent_level'] = $level;
		}

		$npf[] = $block;

		return $npf;
	}

	static private function addListBlock(DOMElement $element, int $uri_id, int $level, array $npf, bool $ordered): array
	{
		foreach ($element->childNodes as $child) {
			switch ($child->nodeName) {
				case 'ul':
					$npf = self::addListBlock($child, $uri_id, $level++, $npf, false);
				case 'ol':
					$npf = self::addListBlock($child, $uri_id, $level++, $npf, true);
				case 'li':
					$text = self::fetchText($child);

					$block = [
						'type'    => 'text',
						'subtype' => $ordered ? 'ordered-list-item' : 'unordered-list-item',
						'text'    => $text['text']
					];
					if ($level > 0) {
						$block['indent_level'] = $level;
					}
					if (!empty($text['formatting'])) {
						$block['formatting'] = $text['formatting'];
					}
					$npf[] = $block;
			}
		}

		return $npf;
	}

	static private function addLinkBlock(int $uri_id, int $level, array $npf): array
	{
		foreach (Post\Media::getByURIId($uri_id, [Post\Media::HTML]) as $link) {
			$host = parse_url($link['url'], PHP_URL_HOST);
			if (in_array($host, ['www.youtube.com', 'youtu.be'])) {
				$block = [
					'type'     => 'video',
					'provider' => 'youtube',
					'url'      => $link['url'],
				];
			} elseif (in_array($host, ['vimeo.com'])) {
				$block = [
					'type'     => 'video',
					'provider' => 'vimeo',
					'url'      => $link['url'],
				];
			} elseif (in_array($host, ['open.spotify.com'])) {
				$block = [
					'type'     => 'audio',
					'provider' => 'spotify',
					'url'      => $link['url'],
				];
			} else {
				$block = [
					'type' => 'link',
					'url'  => $link['url'],
				];
				if (!empty($link['name'])) {
					$block['title'] = $link['name'];
				}
				if (!empty($link['description'])) {
					$block['description'] = $link['description'];
				}
				if (!empty($link['author-name'])) {
					$block['author'] = $link['author-name'];
				}
				if (!empty($link['publisher-name'])) {
					$block['site_name'] = $link['publisher-name'];
				}
			}

			if ($level > 0) {
				$block['indent_level'] = $level;
			}

			$npf[] = self::addPoster($link, $block);
		}
		return $npf;
	}
}
