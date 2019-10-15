<?php
namespace Friendica\Content\Text;

use Friendica\Util\Strings;
use Michelf\MarkdownExtra;

class MarkdownParser extends MarkdownExtra
{
	protected function doAutoLinks($text)
	{
		$text = parent::doAutoLinks($text);

		$text = preg_replace_callback(Strings::autoLinkRegEx(),
			array($this, '_doAutoLinks_url_callback'), $text);

		return $text;
	}
}