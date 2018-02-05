<?php
/**
 * @file include/bbcode.php
 */

use Friendica\Content\Text\BBCode;

function bbcode($Text, $preserve_nl = false, $tryoembed = true, $simplehtml = false, $forplaintext = false)
{
	return BBCode::convert($Text, $preserve_nl, $tryoembed, $simplehtml, $forplaintext);
}
