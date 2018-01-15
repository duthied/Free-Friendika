<?php

/**
 * @file src/Content/Text/Markdown.php
 */

namespace Friendica\Content\Text;

use Friendica\BaseObject;
use Michelf\MarkdownExtra;

/**
 * Friendica-specific usage of Markdown
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Markdown extends BaseObject
{
	/**
	 * Converts a Markdown string into HTML. The hardwrap parameter maximizes
	 * compatibility with Diaspora in spite of the Markdown standard.
	 *
	 * @brief Converts a Markdown string into HTML
	 * @param string $text
	 * @param bool   $hardwrap
	 * @return string
	 */
	public static function convert($text, $hardwrap = true) {
		$stamp1 = microtime(true);

		$MarkdownParser = new MarkdownExtra();
		$MarkdownParser->hard_wrap = $hardwrap;
		$html = $MarkdownParser->transform($text);

		self::getApp()->save_timestamp($stamp1, "parser");

		return $html;
	}
}
