<?php
/**
 * @file src/Content/Text/Plaintext.php
 */
namespace Friendica\Content\Text;

class Plaintext
{
	/**
	 * Shortens message
	 *
	 * @param type $msg
	 * @param type $limit
	 * @return type
	 *
	 * @todo For Twitter URLs aren't shortened, but they have to be calculated as if.
	 */
	public static function shorten($msg, $limit)
	{
		$lines = explode("\n", $msg);
		$msg = "";
		$recycle = html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8');
		$ellipsis = html_entity_decode("&#x2026;", ENT_QUOTES, 'UTF-8');
		foreach ($lines as $row => $line) {
			if (iconv_strlen(trim($msg . "\n" . $line), "UTF-8") <= $limit) {
				$msg = trim($msg . "\n" . $line);
			} elseif (($msg == "") || (($row == 1) && (substr($msg, 0, 4) == $recycle))) {
				// Is the new message empty by now or is it a reshared message?
				$msg = iconv_substr(iconv_substr(trim($msg . "\n" . $line), 0, $limit, "UTF-8"), 0, -3, "UTF-8") . $ellipsis;
			} else {
				break;
			}
		}

		return $msg;
	}
}
