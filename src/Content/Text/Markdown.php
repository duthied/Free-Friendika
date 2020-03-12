<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Contact;

/**
 * Friendica-specific usage of Markdown
 */
class Markdown
{
	/**
	 * Converts a Markdown string into HTML. The hardwrap parameter maximizes
	 * compatibility with Diaspora in spite of the Markdown standard.
	 *
	 * @param string $text
	 * @param bool   $hardwrap
	 * @return string
	 * @throws \Exception
	 */
	public static function convert($text, $hardwrap = true) {
		$stamp1 = microtime(true);

		$MarkdownParser = new MarkdownParser();
		$MarkdownParser->code_class_prefix  = 'language-';
		$MarkdownParser->hard_wrap          = $hardwrap;
		$MarkdownParser->hashtag_protection = true;
		$MarkdownParser->url_filter_func    = function ($url) {
			if (strpos($url, '#') === 0) {
				$url = ltrim($_SERVER['REQUEST_URI'], '/') . $url;
			}
			return  $url;
		};

		$text = self::convertDiasporaMentionsToHtml($text);

		$html = $MarkdownParser->transform($text);

		DI::profiler()->saveTimestamp($stamp1, "parser", System::callstack());

		return $html;
	}

	/**
	 * Replace Diaspora-style mentions in a text since they trip the Markdown parser autolinker.
	 *
	 * @param string $text
	 * @return string
	 */
	private static function convertDiasporaMentionsToHtml(string $text)
	{
		return preg_replace_callback(
			'/([@!]){(?:([^}]+?); ?)?([^} ]+)}/',
			/*
			 * Matching values for the callback
			 * [1] = mention type (@ or !)
			 * [2] = name (optional)
			 * [3] = profile URL
			 */
			function ($matches) {
				if ($matches[3] == '') {
					return '';
				}

				$data = Contact::getDetailsByAddr($matches[3]);

				if (empty($data)) {
					return '';
				}

				$name = $matches[2];

				if ($name == '') {
					$name = $data['name'];
				}

				return $matches[1] . '<a href="' . $data['url'] . '">' . $name . '</a>';
			},
			$text
		);
	}

	/*
	 * we don't want to support a bbcode specific markdown interpreter
	 * and the markdown library we have is pretty good, but provides HTML output.
	 * So we'll use that to convert to HTML, then convert the HTML back to bbcode,
	 * and then clean up a few Diaspora specific constructs.
	 */
	public static function toBBCode($s)
	{
		// The parser cannot handle paragraphs correctly
		$s = str_replace(['</p>', '<p>', '<p dir="ltr">'], ['<br>', '<br>', '<br>'], $s);

		// Escaping hashtags that could be titles
		$s = preg_replace('/^\#([^\s\#])/im', '\#$1', $s);

		$s = self::convert($s);

		$s = HTML::toBBCode($s);

		// protect the recycle symbol from turning into a tag, but without unescaping angles and naked ampersands
		$s = str_replace('&#x2672;', html_entity_decode('&#x2672;', ENT_QUOTES, 'UTF-8'), $s);

		// Convert everything that looks like a link to a link
		$s = preg_replace('/([^\]=]|^)(https?\:\/\/)([a-zA-Z0-9:\/\-?&;.=_~#%$!+,@]+(?<!,))/ism', '$1[url=$2$3]$2$3[/url]', $s);

		//$s = preg_replace("/([^\]\=]|^)(https?\:\/\/)(vimeo|youtu|www\.youtube|soundcloud)([a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1[url=$2$3$4]$2$3$4[/url]',$s);
		$s = BBCode::pregReplaceInTag('/\[url\=?(.*?)\]https?:\/\/www.youtube.com\/watch\?v\=(.*?)\[\/url\]/ism', '[youtube]$2[/youtube]', 'url', $s);
		$s = BBCode::pregReplaceInTag('/\[url\=https?:\/\/www.youtube.com\/watch\?v\=(.*?)\].*?\[\/url\]/ism'   , '[youtube]$1[/youtube]', 'url', $s);
		$s = BBCode::pregReplaceInTag('/\[url\=?(.*?)\]https?:\/\/vimeo.com\/([0-9]+)(.*?)\[\/url\]/ism'        , '[vimeo]$2[/vimeo]'    , 'url', $s);
		$s = BBCode::pregReplaceInTag('/\[url\=https?:\/\/vimeo.com\/([0-9]+)\](.*?)\[\/url\]/ism'              , '[vimeo]$1[/vimeo]'    , 'url', $s);

		// remove duplicate adjacent code tags
		$s = preg_replace('/(\[code\])+(.*?)(\[\/code\])+/ism', '[code]$2[/code]', $s);

		// Don't show link to full picture (until it is fixed)
		$s = BBCode::scaleExternalImages($s);

		return $s;
	}
}
