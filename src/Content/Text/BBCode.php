<?php
/**
 * @file src/Content/Text/BBCode.php
 */
namespace Friendica\Content\Text;

use Friendica\App;
use Friendica\Content\Text\Plaintext;
use Friencica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Object\Image;
use Friendica\Util\ParseUrl;

require_once "include/bbcode.php";
require_once "include/html2plain.php";

class BBCode
{
	/**
	 * @brief Fetches attachment data that were generated the old way
	 *
	 * @param string $body Message body
	 * @return array
	 * 'type' -> Message type ("link", "video", "photo")
	 * 'text' -> Text before the shared message
	 * 'after' -> Text after the shared message
	 * 'image' -> Preview image of the message
	 * 'url' -> Url to the attached message
	 * 'title' -> Title of the attachment
	 * 'description' -> Description of the attachment
	 */
	private static function getOldAttachmentData($body)
	{
		$post = [];

		// Simplify image codes
		$body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $body);

		if (preg_match_all("(\[class=(.*?)\](.*?)\[\/class\])ism", $body, $attached, PREG_SET_ORDER)) {
			foreach ($attached as $data) {
				if (!in_array($data[1], ["type-link", "type-video", "type-photo"])) {
					continue;
				}

				$post["type"] = substr($data[1], 5);

				$pos = strpos($body, $data[0]);
				if ($pos > 0) {
					$post["text"] = trim(substr($body, 0, $pos));
					$post["after"] = trim(substr($body, $pos + strlen($data[0])));
				} else {
					$post["text"] = trim(str_replace($data[0], "", $body));
				}

				$attacheddata = $data[2];

				$URLSearchString = "^\[\]";

				if (preg_match("/\[img\]([$URLSearchString]*)\[\/img\]/ism", $attacheddata, $matches)) {

					$picturedata = Image::getInfoFromURL($matches[1]);

					if (($picturedata[0] >= 500) && ($picturedata[0] >= $picturedata[1])) {
						$post["image"] = $matches[1];
					} else {
						$post["preview"] = $matches[1];
					}
				}

				if (preg_match("/\[bookmark\=([$URLSearchString]*)\](.*?)\[\/bookmark\]/ism", $attacheddata, $matches)) {
					$post["url"] = $matches[1];
					$post["title"] = $matches[2];
				}
				if (($post["url"] == "") && (in_array($post["type"], ["link", "video"]))
					&& preg_match("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", $attacheddata, $matches)) {
					$post["url"] = $matches[1];
				}

				// Search for description
				if (preg_match("/\[quote\](.*?)\[\/quote\]/ism", $attacheddata, $matches)) {
					$post["description"] = $matches[1];
				}
			}
		}
		return $post;
	}

	/**
	 * @brief Fetches attachment data that were generated with the "attachment" element
	 *
	 * @param string $body Message body
	 * @return array
	 * 'type' -> Message type ("link", "video", "photo")
	 * 'text' -> Text before the shared message
	 * 'after' -> Text after the shared message
	 * 'image' -> Preview image of the message
	 * 'url' -> Url to the attached message
	 * 'title' -> Title of the attachment
	 * 'description' -> Description of the attachment
	 */
	public static function getAttachmentData($body)
	{
		$data = [];

		if (!preg_match("/(.*)\[attachment(.*?)\](.*?)\[\/attachment\](.*)/ism", $body, $match)) {
			return self::getOldAttachmentData($body);
		}

		$attributes = $match[2];

		$data["text"] = trim($match[1]);

		$type = "";
		preg_match("/type='(.*?)'/ism", $attributes, $matches);
		if (x($matches, 1)) {
			$type = strtolower($matches[1]);
		}

		preg_match('/type="(.*?)"/ism', $attributes, $matches);
		if (x($matches, 1)) {
			$type = strtolower($matches[1]);
		}

		if ($type == "") {
			return [];
		}

		if (!in_array($type, ["link", "audio", "photo", "video"])) {
			return [];
		}

		if ($type != "") {
			$data["type"] = $type;
		}

		$url = "";
		preg_match("/url='(.*?)'/ism", $attributes, $matches);
		if (x($matches, 1)) {
			$url = $matches[1];
		}

		preg_match('/url="(.*?)"/ism', $attributes, $matches);
		if (x($matches, 1)) {
			$url = $matches[1];
		}

		if ($url != "") {
			$data["url"] = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
		}

		$title = "";
		preg_match("/title='(.*?)'/ism", $attributes, $matches);
		if (x($matches, 1)) {
			$title = $matches[1];
		}

		preg_match('/title="(.*?)"/ism', $attributes, $matches);
		if (x($matches, 1)) {
			$title = $matches[1];
		}

		if ($title != "") {
			$title = bbcode(html_entity_decode($title, ENT_QUOTES, 'UTF-8'), false, false, true);
			$title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
			$title = str_replace(["[", "]"], ["&#91;", "&#93;"], $title);
			$data["title"] = $title;
		}

		$image = "";
		preg_match("/image='(.*?)'/ism", $attributes, $matches);
		if (x($matches, 1)) {
			$image = $matches[1];
		}

		preg_match('/image="(.*?)"/ism', $attributes, $matches);
		if (x($matches, 1)) {
			$image = $matches[1];
		}

		if ($image != "") {
			$data["image"] = html_entity_decode($image, ENT_QUOTES, 'UTF-8');
		}

		$preview = "";
		preg_match("/preview='(.*?)'/ism", $attributes, $matches);
		if (x($matches, 1)) {
			$preview = $matches[1];
		}

		preg_match('/preview="(.*?)"/ism', $attributes, $matches);
		if (x($matches, 1)) {
			$preview = $matches[1];
		}

		if ($preview != "") {
			$data["preview"] = html_entity_decode($preview, ENT_QUOTES, 'UTF-8');
		}

		$data["description"] = trim($match[3]);

		$data["after"] = trim($match[4]);

		return $data;
	}

	public static function getAttachedData($body, $item = [])
	{
		/*
		- text:
		- type: link, video, photo
		- title:
		- url:
		- image:
		- description:
		- (thumbnail)
		*/

		$has_title = !empty($item['title']);
		$plink = (!empty($item['plink']) ? $item['plink'] : '');
		$post = self::getAttachmentData($body);

		// if nothing is found, it maybe having an image.
		if (!isset($post["type"])) {
			// Simplify image codes
			$body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $body);

			$URLSearchString = "^\[\]";
			if (preg_match_all("(\[url=([$URLSearchString]*)\]\s*\[img\]([$URLSearchString]*)\[\/img\]\s*\[\/url\])ism", $body, $pictures, PREG_SET_ORDER)) {
				if ((count($pictures) == 1) && !$has_title) {
					// Checking, if the link goes to a picture
					$data = ParseUrl::getSiteinfoCached($pictures[0][1], true);

					// Workaround:
					// Sometimes photo posts to the own album are not detected at the start.
					// So we seem to cannot use the cache for these cases. That's strange.
					if (($data["type"] != "photo") && strstr($pictures[0][1], "/photos/")) {
						$data = ParseUrl::getSiteinfo($pictures[0][1], true);
					}

					if ($data["type"] == "photo") {
						$post["type"] = "photo";
						if (isset($data["images"][0])) {
							$post["image"] = $data["images"][0]["src"];
							$post["url"] = $data["url"];
						} else {
							$post["image"] = $data["url"];
						}

						$post["preview"] = $pictures[0][2];
						$post["text"] = str_replace($pictures[0][0], "", $body);
					} else {
						$imgdata = Image::getInfoFromURL($pictures[0][1]);
						if (substr($imgdata["mime"], 0, 6) == "image/") {
							$post["type"] = "photo";
							$post["image"] = $pictures[0][1];
							$post["preview"] = $pictures[0][2];
							$post["text"] = str_replace($pictures[0][0], "", $body);
						}
					}
				} elseif (count($pictures) > 0) {
					$post["type"] = "link";
					$post["url"] = $plink;
					$post["image"] = $pictures[0][2];
					$post["text"] = $body;
				}
			} elseif (preg_match_all("(\[img\]([$URLSearchString]*)\[\/img\])ism", $body, $pictures, PREG_SET_ORDER)) {
				if ((count($pictures) == 1) && !$has_title) {
					$post["type"] = "photo";
					$post["image"] = $pictures[0][1];
					$post["text"] = str_replace($pictures[0][0], "", $body);
				} elseif (count($pictures) > 0) {
					$post["type"] = "link";
					$post["url"] = $plink;
					$post["image"] = $pictures[0][1];
					$post["text"] = $body;
				}
			}

			// Test for the external links
			preg_match_all("(\[url\]([$URLSearchString]*)\[\/url\])ism", $body, $links1, PREG_SET_ORDER);
			preg_match_all("(\[url\=([$URLSearchString]*)\].*?\[\/url\])ism", $body, $links2, PREG_SET_ORDER);

			$links = array_merge($links1, $links2);

			// If there is only a single one, then use it.
			// This should cover link posts via API.
			if ((count($links) == 1) && !isset($post["preview"]) && !$has_title) {
				$post["type"] = "link";
				$post["text"] = trim($body);
				$post["url"] = $links[0][1];
			}

			// Now count the number of external media links
			preg_match_all("(\[vimeo\](.*?)\[\/vimeo\])ism", $body, $links1, PREG_SET_ORDER);
			preg_match_all("(\[youtube\\](.*?)\[\/youtube\\])ism", $body, $links2, PREG_SET_ORDER);
			preg_match_all("(\[video\\](.*?)\[\/video\\])ism", $body, $links3, PREG_SET_ORDER);
			preg_match_all("(\[audio\\](.*?)\[\/audio\\])ism", $body, $links4, PREG_SET_ORDER);

			// Add them to the other external links
			$links = array_merge($links, $links1, $links2, $links3, $links4);

			// Are there more than one?
			if (count($links) > 1) {
				// The post will be the type "text", which means a blog post
				unset($post["type"]);
				$post["url"] = $plink;
			}

			if (!isset($post["type"])) {
				$post["type"] = "text";
				$post["text"] = trim($body);
			}
		} elseif (isset($post["url"]) && ($post["type"] == "video")) {
			$data = ParseUrl::getSiteinfoCached($post["url"], true);

			if (isset($data["images"][0])) {
				$post["image"] = $data["images"][0]["src"];
			}
		}

		return $post;
	}

	/**
	 * @brief Convert a message into plaintext for connectors to other networks
	 *
	 * @param array $b The message array that is about to be posted
	 * @param int $limit The maximum number of characters when posting to that network
	 * @param bool $includedlinks Has an attached link to be included into the message?
	 * @param int $htmlmode This triggers the behaviour of the bbcode conversion
	 * @param string $target_network Name of the network where the post should go to.
	 *
	 * @return string The converted message
	 */
	public static function toPlaintext($b, $limit = 0, $includedlinks = false, $htmlmode = 2, $target_network = "")
	{
		// Remove the hash tags
		$URLSearchString = "^\[\]";
		$body = preg_replace("/([#@])\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '$1$3', $b["body"]);

		// Add an URL element if the text contains a raw link
		$body = preg_replace("/([^\]\='".'"'."]|^)(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1[url]$2[/url]', $body);

		// Remove the abstract
		$body = remove_abstract($body);

		// At first look at data that is attached via "type-..." stuff
		// This will hopefully replaced with a dedicated bbcode later
		//$post = self::getAttachedData($b["body"]);
		$post = self::getAttachedData($body, $b);

		if (($b["title"] != "") && ($post["text"] != "")) {
			$post["text"] = trim($b["title"]."\n\n".$post["text"]);
		} elseif ($b["title"] != "") {
			$post["text"] = trim($b["title"]);
		}

		$abstract = "";

		// Fetch the abstract from the given target network
		if ($target_network != "") {
			$default_abstract = fetch_abstract($b["body"]);
			$abstract = fetch_abstract($b["body"], $target_network);

			// If we post to a network with no limit we only fetch
			// an abstract exactly for this network
			if (($limit == 0) && ($abstract == $default_abstract)) {
				$abstract = "";
			}
		} else {// Try to guess the correct target network
			switch ($htmlmode) {
				case 8:
					$abstract = fetch_abstract($b["body"], NETWORK_TWITTER);
					break;
				case 7:
					$abstract = fetch_abstract($b["body"], NETWORK_STATUSNET);
					break;
				case 6:
					$abstract = fetch_abstract($b["body"], NETWORK_APPNET);
					break;
				default: // We don't know the exact target.
					// We fetch an abstract since there is a posting limit.
					if ($limit > 0) {
						$abstract = fetch_abstract($b["body"]);
					}
			}
		}

		if ($abstract != "") {
			$post["text"] = $abstract;

			if ($post["type"] == "text") {
				$post["type"] = "link";
				$post["url"] = $b["plink"];
			}
		}

		$html = bbcode($post["text"].$post["after"], false, false, $htmlmode);
		$msg = html2plain($html, 0, true);
		$msg = trim(html_entity_decode($msg, ENT_QUOTES, 'UTF-8'));

		$link = "";
		if ($includedlinks) {
			if ($post["type"] == "link") {
				$link = $post["url"];
			} elseif ($post["type"] == "text") {
				$link = $post["url"];
			} elseif ($post["type"] == "video") {
				$link = $post["url"];
			} elseif ($post["type"] == "photo") {
				$link = $post["image"];
			}

			if (($msg == "") && isset($post["title"])) {
				$msg = trim($post["title"]);
			}

			if (($msg == "") && isset($post["description"])) {
				$msg = trim($post["description"]);
			}

			// If the link is already contained in the post, then it neeedn't to be added again
			// But: if the link is beyond the limit, then it has to be added.
			if (($link != "") && strstr($msg, $link)) {
				$pos = strpos($msg, $link);

				// Will the text be shortened in the link?
				// Or is the link the last item in the post?
				if (($limit > 0) && ($pos < $limit) && (($pos + 23 > $limit) || ($pos + strlen($link) == strlen($msg)))) {
					$msg = trim(str_replace($link, "", $msg));
				} elseif (($limit == 0) || ($pos < $limit)) {
					// The limit has to be increased since it will be shortened - but not now
					// Only do it with Twitter (htmlmode = 8)
					if (($limit > 0) && (strlen($link) > 23) && ($htmlmode == 8)) {
						$limit = $limit - 23 + strlen($link);
					}

					$link = "";

					if ($post["type"] == "text") {
						unset($post["url"]);
					}
				}
			}
		}

		if ($limit > 0) {
			// Reduce multiple spaces
			// When posted to a network with limited space, we try to gain space where possible
			while (strpos($msg, "  ") !== false) {
				$msg = str_replace("  ", " ", $msg);
			}

			// Twitter is using its own limiter, so we always assume that shortened links will have this length
			if (iconv_strlen($link, "UTF-8") > 0) {
				$limit = $limit - 23;
			}

			if (iconv_strlen($msg, "UTF-8") > $limit) {
				if (($post["type"] == "text") && isset($post["url"])) {
					$post["url"] = $b["plink"];
				} elseif (!isset($post["url"])) {
					$limit = $limit - 23;
					$post["url"] = $b["plink"];
				// Which purpose has this line? It is now uncommented, but left as a reminder
				//} elseif (strpos($b["body"], "[share") !== false) {
				//	$post["url"] = $b["plink"];
				} elseif (PConfig::get($b["uid"], "system", "no_intelligent_shortening")) {
					$post["url"] = $b["plink"];
				}
				$msg = Plaintext::shorten($msg, $limit);
			}
		}

		$post["text"] = trim($msg);

		return($post);
	}

	public static function scaleExternalImages($srctext, $include_link = true, $scale_replace = false)
	{
		// Suppress "view full size"
		if (intval(Config::get('system', 'no_view_full_size'))) {
			$include_link = false;
		}

		// Picture addresses can contain special characters
		$s = htmlspecialchars_decode($srctext);

		$matches = null;
		$c = preg_match_all('/\[img.*?\](.*?)\[\/img\]/ism', $s, $matches, PREG_SET_ORDER);
		if ($c) {
			foreach ($matches as $mtch) {
				logger('scale_external_image: ' . $mtch[1]);

				$hostname = str_replace('www.', '', substr(System::baseUrl(), strpos(System::baseUrl(), '://') + 3));
				if (stristr($mtch[1], $hostname)) {
					continue;
				}

				// $scale_replace, if passed, is an array of two elements. The
				// first is the name of the full-size image. The second is the
				// name of a remote, scaled-down version of the full size image.
				// This allows Friendica to display the smaller remote image if
				// one exists, while still linking to the full-size image
				if ($scale_replace) {
					$scaled = str_replace($scale_replace[0], $scale_replace[1], $mtch[1]);
				} else {
					$scaled = $mtch[1];
				}
				$i = self::fetchURL($scaled);
				if (! $i) {
					return $srctext;
				}

				// guess mimetype from headers or filename
				$type = Image::guessType($mtch[1], true);

				if ($i) {
					$Image = new Image($i, $type);
					if ($Image->isValid()) {
						$orig_width = $Image->getWidth();
						$orig_height = $Image->getHeight();

						if ($orig_width > 640 || $orig_height > 640) {
							$Image->scaleDown(640);
							$new_width = $Image->getWidth();
							$new_height = $Image->getHeight();
							logger('scale_external_images: ' . $orig_width . '->' . $new_width . 'w ' . $orig_height . '->' . $new_height . 'h' . ' match: ' . $mtch[0], LOGGER_DEBUG);
							$s = str_replace(
								$mtch[0],
								'[img=' . $new_width . 'x' . $new_height. ']' . $scaled . '[/img]'
								. "\n" . (($include_link)
									? '[url=' . $mtch[1] . ']' . L10n::t('view full size') . '[/url]' . "\n"
									: ''),
								$s
							);
							logger('scale_external_images: new string: ' . $s, LOGGER_DEBUG);
						}
					}
				}
			}
		}

		// replace the special char encoding
		$s = htmlspecialchars($s, ENT_NOQUOTES, 'UTF-8');
		return $s;
	}
}
