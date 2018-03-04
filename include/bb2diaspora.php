<?php

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Markdown;
use Friendica\Core\Addon;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Model\Contact;
use Friendica\Network\Probe;
use Friendica\Util\DateTimeFormat;
use League\HTMLToMarkdown\HtmlConverter;

require_once 'include/event.php';
require_once 'include/html2bbcode.php';

function diaspora2bb($s) {
	return Markdown::toBBCode($s);
}

/**
 * @brief Callback function to replace a Friendica style mention in a mention for Diaspora
 *
 * @param array $match Matching values for the callback
 * @return string Replaced mention
 */
function diaspora_mentions($match) {

	$contact = Contact::getDetailsByURL($match[3]);

	if (!x($contact, 'addr')) {
		$contact = Probe::uri($match[3]);
	}

	if (!x($contact, 'addr')) {
		return $match[0];
	}

	$mention = '@{' . $match[2] . '; ' . $contact['addr'] . '}';
	return $mention;
}

/**
 * @brief Converts a BBCode text into Markdown
 *
 * This function converts a BBCode item body to be sent to Markdown-enabled
 * systems like Diaspora and Libertree
 *
 * @param string $Text
 * @param bool $fordiaspora Diaspora requires more changes than Libertree
 * @return string
 */
function bb2diaspora($Text, $fordiaspora = true) {
	$a = get_app();

	$OriginalText = $Text;

	// Since Diaspora is creating a summary for links, this function removes them before posting
	if ($fordiaspora) {
		$Text = BBCode::removeShareInformation($Text);
	}

	/**
	 * Transform #tags, strip off the [url] and replace spaces with underscore
	 */
	$URLSearchString = "^\[\]";
	$Text = preg_replace_callback("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/i",
		function ($matches) {
			return '#' . str_replace(' ', '_', $matches[2]);
		}
	, $Text);

	// Converting images with size parameters to simple images. Markdown doesn't know it.
	$Text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $Text);

	// Extracting multi-line code blocks before the whitespace processing/code highlighter in BBCode::convert()
	$codeblocks = [];

	$Text = preg_replace_callback("#\[code(?:=([^\]]*))?\](.*?)\[\/code\]#is",
		function ($matches) use (&$codeblocks) {
			$return = $matches[0];
			if (strpos($matches[2], "\n") !== false) {
				$return = '#codeblock-' . count($codeblocks) . '#';

				$prefix = '````' . $matches[1] . PHP_EOL;
				$codeblocks[] = $prefix . trim($matches[2]) . PHP_EOL . '````';
			}
			return $return;
		}
	, $Text);

	// Convert it to HTML - don't try oembed
	if ($fordiaspora) {
		$Text = BBCode::convert($Text, false, 3);

		// Add all tags that maybe were removed
		if (preg_match_all("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", $OriginalText, $tags)) {
			$tagline = "";
			foreach ($tags[2] as $tag) {
				$tag = html_entity_decode($tag, ENT_QUOTES, 'UTF-8');
				if (!strpos(html_entity_decode($Text, ENT_QUOTES, 'UTF-8'), '#' . $tag)) {
					$tagline .= '#' . $tag . ' ';
				}
			}
			$Text = $Text." ".$tagline;
		}
	} else {
		$Text = BBCode::convert($Text, false, 4);
	}

	// mask some special HTML chars from conversation to markdown
	$Text = str_replace(['&lt;', '&gt;', '&amp;'], ['&_lt_;', '&_gt_;', '&_amp_;'], $Text);

	// If a link is followed by a quote then there should be a newline before it
	// Maybe we should make this newline at every time before a quote.
	$Text = str_replace(["</a><blockquote>"], ["</a><br><blockquote>"], $Text);

	$stamp1 = microtime(true);

	// Now convert HTML to Markdown
	$converter = new HtmlConverter();
	$Text = $converter->convert($Text);

	// unmask the special chars back to HTML
	$Text = str_replace(['&\_lt\_;', '&\_gt\_;', '&\_amp\_;'], ['&lt;', '&gt;', '&amp;'], $Text);

	$a->save_timestamp($stamp1, "parser");

	// Libertree has a problem with escaped hashtags.
	$Text = str_replace(['\#'], ['#'], $Text);

	// Remove any leading or trailing whitespace, as this will mess up
	// the Diaspora signature verification and cause the item to disappear
	$Text = trim($Text);

	if ($fordiaspora) {
		$URLSearchString = "^\[\]";
		$Text = preg_replace_callback("/([@]\[(.*?)\])\(([$URLSearchString]*?)\)/ism", 'diaspora_mentions', $Text);
	}

	// Restore code blocks
	$Text = preg_replace_callback('/#codeblock-([0-9]+)#/iU',
		function ($matches) use ($codeblocks) {
            $return = '';
            if (isset($codeblocks[intval($matches[1])])) {
                $return = $codeblocks[$matches[1]];
            }
			return $return;
		}
	, $Text);

	Addon::callHooks('bb2diaspora',$Text);

	return $Text;
}

function unescape_underscores_in_links($m) {
	$y = str_replace('\\_', '_', $m[2]);
	return('[' . $m[1] . '](' . $y . ')');
}

function format_event_diaspora($ev) {
	if (! ((is_array($ev)) && count($ev))) {
		return '';
	}

	$bd_format = L10n::t('l F d, Y \@ g:i A') ; // Friday January 18, 2011 @ 8 AM

	$o = 'Friendica event notification:' . "\n";

	$o .= '**' . (($ev['summary']) ? bb2diaspora($ev['summary']) : bb2diaspora($ev['desc'])) .  '**' . "\n";

	// @todo What. Is. Going. On. With. This. Useless. Ternary. Operator? - mrpetovan
	$o .= L10n::t('Starts:') . ' ' . '[' . day_translate(
			$ev['adjust'] ? DateTimeFormat::utc($ev['start'], $bd_format) : DateTimeFormat::utc($ev['start'], $bd_format)
		)
		.  '](' . System::baseUrl() . '/localtime/?f=&time=' . urlencode(DateTimeFormat::utc($ev['start'])) . ")\n";

	if (! $ev['nofinish']) {
		$o .= L10n::t('Finishes:') . ' ' . '[' . day_translate(
				$ev['adjust'] ? DateTimeFormat::utc($ev['finish'], $bd_format) : DateTimeFormat::utc($ev['finish'], $bd_format)
			)
			. '](' . System::baseUrl() . '/localtime/?f=&time=' . urlencode(DateTimeFormat::utc($ev['finish'])) . ")\n";
	}

	if (strlen($ev['location'])) {
		$o .= L10n::t('Location:') . bb2diaspora($ev['location'])
			. "\n";
	}

	$o .= "\n";
	return $o;
}
