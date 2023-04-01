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
		$bbcode = self::prepareBody($bbcode);

		$html = BBCode::convert($bbcode, false, BBCode::CONNECTORS);
		if (empty($html)) {
			return [];
		}

		$doc = new DOMDocument();
		$doc->formatOutput = true;
		if (!@$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'))) {
			return [];
		}

		$element = $doc->getElementsByTagName('body')->item(0);
		echo $element->ownerDocument->saveHTML($element) . "\n";

		$npf        = [];
		$text       = '';
		$formatting = [];

		self::routeChildren($element, $uri_id, true, [], $npf, $text, $formatting);

		return self::addLinkBlockForUriId($uri_id, 0, $npf);
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

	static private function routeChildren(DOMElement $element, int $uri_id, bool $parse_structure, array $callstack, array &$npf, string &$text, array &$formatting)
	{
		if ($parse_structure && $text) {
			self::addBlock($text, $formatting, $npf, $callstack);
		}

		$callstack[] = $element->nodeName;
		$level = self::getLevelByCallstack($callstack);

		foreach ($element->childNodes as $child) {
			switch ($child->nodeName) {
				case 'b':
				case 'strong':
					self::addFormatting($child, $uri_id, 'bold', $callstack, $npf, $text, $formatting);
					break;
	
				case 'i':
				case 'em':
					self::addFormatting($child, $uri_id, 'italic', $callstack, $npf, $text, $formatting);
					break;
	
				case 's':
					self::addFormatting($child, $uri_id, 'strikethrough', $callstack, $npf, $text, $formatting);
					break;

				case 'u':
				case 'span':
					self::addFormatting($child, $uri_id, '', $callstack, $npf, $text, $formatting);
					break;

				case 'hr':
				case 'br':
					if (!empty($text)) {
						$text .= "\n";
					}
					break;
		
				case '#text':
					$text .= $child->textContent;
					break;

				case 'table':
				case 'summary':
					// Ignore tables and spoilers
					break;

				case 'a':
					if ($text) {
						self::addInlineLink($child, $uri_id, $callstack, $npf, $text, $formatting);
					} else {
						$npf = self::addLinkBlock($child, $uri_id, $level, $npf);
					}
					break;

				case 'img':
					$npf = self::addImageBlock($child, $uri_id, $level, $npf);
					break;

				case 'ol':
				case 'div':
				case 'h1':
				case 'h2':
				case 'h3':
				case 'h4':
				case 'h5':
				case 'h6':
				case 'blockquote':
				case 'p':
				case 'pre':
				case 'code':
				case 'ul':
				case 'li':
				case 'details':
					self::routeChildren($child, $uri_id, true, $callstack, $npf, $text, $formatting);
					break;

				default:
					print_r($npf);
					print_r($callstack);
					die($child . "\n");
			}
		}

		if ($parse_structure && $text) {
			self::addBlock($text, $formatting, $npf, $callstack);
		}
	}

	static private function getLevelByCallstack($callstack): int
	{
		$level = 0;
		foreach ($callstack as $entry) {
			if (in_array($entry, ['ol', 'ul', 'blockquote'])) {
				++$level;
			}
		}
		return max(0, $level - 1);
	}

	static private function getSubTypeByCallstack($callstack): string
	{
		$subtype = '';
		foreach ($callstack as $entry) {
			switch ($entry) {
				case 'ol':
					$subtype = 'ordered-list-item';
					break;

				case 'ul':
					$subtype = 'unordered-list-item';
					break;

				case 'h1':
					$subtype = 'heading1';
					break;
	
				case 'h2':
					$subtype = 'heading1';
					break;
	
				case 'h3':
					$subtype = 'heading1';
					break;
	
				case 'h4':
					$subtype = 'heading2';
					break;
	
				case 'h5':
					$subtype = 'heading2';
					break;
	
				case 'h6':
					$subtype = 'heading2';
					break;
	
				case 'blockquote':
				case 'pre':
				case 'code':
					$subtype = 'indented';
					break;
			}
		}
		return $subtype;
	}

	static private function addFormatting(DOMElement $element, int $uri_id, string $type, array $callstack, array &$npf, string &$text, array &$formatting)
	{
		$start = mb_strlen($text);
		self::routeChildren($element, $uri_id, false, $callstack, $npf, $text, $formatting);

		if (!empty($type)) {
			$formatting[] = [
				'start' => $start,
				'end'   => mb_strlen($text),
				'type'  => $type
			];
		}
	}

	static private function addInlineLink(DOMElement $element, int $uri_id, array $callstack, array &$npf, string &$text, array &$formatting)
	{
		$start = mb_strlen($text);
		self::routeChildren($element, $uri_id, false, $callstack, $npf, $text, $formatting);

		$attributes = [];
		foreach ($element->attributes as $key => $attribute) {
			$attributes[$key] = trim($attribute->value);
		}
		if (!empty($attributes['href'])) {
			$formatting[] = [
				'start' => $start,
				'end'   => mb_strlen($text),
				'type'  => 'link',
				'url'   => $attributes['href']
			];
		}
	}

	static private function addBlock(string &$text, array &$formatting, array &$npf, array $callstack)
	{
		$block = [
			'callstack' => $callstack,
			'type'      => 'text',
			'text'      => $text,
		];

		if (!empty($formatting)) {
			$block['formatting'] = $formatting;
		}

		$level = self::getLevelByCallstack($callstack);
		if ($level > 0) {
			$block['indent_level'] = $level;
		}

		$subtype = self::getSubTypeByCallstack($callstack);
		if ($subtype) {
			$block['subtype'] = $subtype;
		}

		$npf[] = $block;
		$text = '';
		$formatting = [];
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

	static private function addLinkBlockForUriId(int $uri_id, int $level, array $npf): array
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

	static private function addImageBlock(DOMElement $element, int $uri_id, int $level, array $npf): array
	{
		$attributes = [];
		foreach ($element->attributes as $key => $attribute) {
			$attributes[$key] = trim($attribute->value);
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

		if (!empty($attributes['title']) && (($attributes['alt'] ?? '') != $attributes['title'])) {
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

	static private function addLinkBlock(DOMElement $element, int $uri_id, int $level, array $npf): array
	{
		$attributes = [];
		foreach ($element->attributes as $key => $attribute) {
			$attributes[$key] = trim($attribute->value);
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
}
