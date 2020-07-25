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

namespace Friendica\Protocol;

use DOMDocument;
use DOMXPath;
use Friendica\Content\PageInfo;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Cache\Duration;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Network\HTTPRequest;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\ParseUrl;
use Friendica\Util\Strings;
use Friendica\Util\XML;

/**
 * This class contain functions to import feeds (RSS/RDF/Atom)
 */
class Feed
{
	/**
	 * consume - process atom feed and update anything/everything we might need to update
	 *
	 * $xml = the (atom) feed to consume - RSS isn't as fully supported but may work for simple feeds.
	 *
	 * $importer = the contact_record (joined to user_record) of the local user who owns this relationship.
	 *             It is this person's stuff that is going to be updated.
	 * $contact =  the person who is sending us stuff. If not set, we MAY be processing a "follow" activity
	 *             from an external network and MAY create an appropriate contact record. Otherwise, we MUST
	 *             have a contact record.
	 * $hub = should we find a hub declation in the feed, pass it back to our calling process, who might (or
	 *        might not) try and subscribe to it.
	 * $datedir sorts in reverse order
	 * $pass - by default ($pass = 0) we cannot guarantee that a parent item has been
	 *      imported prior to its children being seen in the stream unless we are certain
	 *      of how the feed is arranged/ordered.
	 * With $pass = 1, we only pull parent items out of the stream.
	 * With $pass = 2, we only pull children (comments/likes).
	 *
	 * So running this twice, first with pass 1 and then with pass 2 will do the right
	 * thing regardless of feed ordering. This won't be adequate in a fully-threaded
	 * model where comments can have sub-threads. That would require some massive sorting
	 * to get all the feed items into a mostly linear ordering, and might still require
	 * recursion.
	 *
	 * @param       $xml
	 * @param array $importer
	 * @param array $contact
	 * @param       $hub
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function consume($xml, array $importer, array $contact, &$hub)
	{
		if ($contact['network'] === Protocol::OSTATUS) {
			Logger::info('Consume OStatus messages');
			OStatus::import($xml, $importer, $contact, $hub);

			return;
		}

		if ($contact['network'] === Protocol::FEED) {
			Logger::info('Consume feeds');
			self::import($xml, $importer, $contact);

			return;
		}

		if ($contact['network'] === Protocol::DFRN) {
			Logger::info('Consume DFRN messages');
			$dfrn_importer = DFRN::getImporter($contact['id'], $importer['uid']);
			if (!empty($dfrn_importer)) {
				Logger::info('Now import the DFRN feed');
				DFRN::import($xml, $dfrn_importer, true);
				return;
			}
		}
	}

	/**
	 * Read a RSS/RDF/Atom feed and create an item entry for it
	 *
	 * @param string $xml      The feed data
	 * @param array  $importer The user record of the importer
	 * @param array  $contact  The contact record of the feed
	 *
	 * @return array Returns the header and the first item in dry run mode
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function import($xml, array $importer = [], array $contact = [])
	{
		$dryRun = empty($importer) && empty($contact);

		if ($dryRun) {
			Logger::info("Test Atom/RSS feed");
		} else {
			Logger::info("Import Atom/RSS feed '" . $contact["name"] . "' (Contact " . $contact["id"] . ") for user " . $importer["uid"]);
		}

		if (empty($xml)) {
			Logger::info('XML is empty.');
			return [];
		}

		if (!empty($contact['poll'])) {
			$basepath = $contact['poll'];
		} elseif (!empty($contact['url'])) {
			$basepath = $contact['url'];
		} else {
			$basepath = '';
		}

		$doc = new DOMDocument();
		@$doc->loadXML(trim($xml));
		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('atom', ActivityNamespace::ATOM1);
		$xpath->registerNamespace('dc', "http://purl.org/dc/elements/1.1/");
		$xpath->registerNamespace('content', "http://purl.org/rss/1.0/modules/content/");
		$xpath->registerNamespace('rdf', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$xpath->registerNamespace('rss', "http://purl.org/rss/1.0/");
		$xpath->registerNamespace('media', "http://search.yahoo.com/mrss/");
		$xpath->registerNamespace('poco', ActivityNamespace::POCO);

		$author = [];
		$entries = null;

		// Is it RDF?
		if ($xpath->query('/rdf:RDF/rss:channel')->length > 0) {
			$author["author-link"] = XML::getFirstNodeValue($xpath, '/rdf:RDF/rss:channel/rss:link/text()');
			$author["author-name"] = XML::getFirstNodeValue($xpath, '/rdf:RDF/rss:channel/rss:title/text()');

			if (empty($author["author-name"])) {
				$author["author-name"] = XML::getFirstNodeValue($xpath, '/rdf:RDF/rss:channel/rss:description/text()');
			}
			$entries = $xpath->query('/rdf:RDF/rss:item');
		}

		// Is it Atom?
		if ($xpath->query('/atom:feed')->length > 0) {
			$alternate = XML::getFirstAttributes($xpath, "atom:link[@rel='alternate']");
			if (is_object($alternate)) {
				foreach ($alternate AS $attribute) {
					if ($attribute->name == "href") {
						$author["author-link"] = $attribute->textContent;
					}
				}
			}

			if (empty($author["author-link"])) {
				$self = XML::getFirstAttributes($xpath, "atom:link[@rel='self']");
				if (is_object($self)) {
					foreach ($self AS $attribute) {
						if ($attribute->name == "href") {
							$author["author-link"] = $attribute->textContent;
						}
					}
				}
			}

			if (empty($author["author-link"])) {
				$author["author-link"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:id/text()');
			}
			$author["author-avatar"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:logo/text()');

			$author["author-name"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:title/text()');

			if (empty($author["author-name"])) {
				$author["author-name"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:subtitle/text()');
			}

			if (empty($author["author-name"])) {
				$author["author-name"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:author/atom:name/text()');
			}

			$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:displayName/text()');
			if ($value != "") {
				$author["author-name"] = $value;
			}

			if ($dryRun) {
				$author["author-id"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:author/atom:id/text()');

				// See https://tools.ietf.org/html/rfc4287#section-3.2.2
				$value = XML::getFirstNodeValue($xpath, 'atom:author/atom:uri/text()');
				if ($value != "") {
					$author["author-link"] = $value;
				}

				$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:preferredUsername/text()');
				if ($value != "") {
					$author["author-nick"] = $value;
				}

				$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:address/poco:formatted/text()');
				if ($value != "") {
					$author["author-location"] = $value;
				}

				$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:note/text()');
				if ($value != "") {
					$author["author-about"] = $value;
				}

				$avatar = XML::getFirstAttributes($xpath, "atom:author/atom:link[@rel='avatar']");
				if (is_object($avatar)) {
					foreach ($avatar AS $attribute) {
						if ($attribute->name == "href") {
							$author["author-avatar"] = $attribute->textContent;
						}
					}
				}
			}

			$author["edited"] = $author["created"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:updated/text()');

			$author["app"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:generator/text()');

			$entries = $xpath->query('/atom:feed/atom:entry');
		}

		// Is it RSS?
		if ($xpath->query('/rss/channel')->length > 0) {
			$author["author-link"] = XML::getFirstNodeValue($xpath, '/rss/channel/link/text()');

			$author["author-name"] = XML::getFirstNodeValue($xpath, '/rss/channel/title/text()');
			$author["author-avatar"] = XML::getFirstNodeValue($xpath, '/rss/channel/image/url/text()');

			if (empty($author["author-name"])) {
				$author["author-name"] = XML::getFirstNodeValue($xpath, '/rss/channel/copyright/text()');
			}

			if (empty($author["author-name"])) {
				$author["author-name"] = XML::getFirstNodeValue($xpath, '/rss/channel/description/text()');
			}

			$author["edited"] = $author["created"] = XML::getFirstNodeValue($xpath, '/rss/channel/pubDate/text()');

			$author["app"] = XML::getFirstNodeValue($xpath, '/rss/channel/generator/text()');

			$entries = $xpath->query('/rss/channel/item');
		}

		if (!$dryRun) {
			$author["author-link"] = $contact["url"];

			if (empty($author["author-name"])) {
				$author["author-name"] = $contact["name"];
			}

			$author["author-avatar"] = $contact["thumb"];

			$author["owner-link"] = $contact["url"];
			$author["owner-name"] = $contact["name"];
			$author["owner-avatar"] = $contact["thumb"];
		}

		$header = [];
		$header["uid"] = $importer["uid"] ?? 0;
		$header["network"] = Protocol::FEED;
		$header["wall"] = 0;
		$header["origin"] = 0;
		$header["gravity"] = GRAVITY_PARENT;
		$header["private"] = Item::PUBLIC;
		$header["verb"] = Activity::POST;
		$header["object-type"] = Activity\ObjectType::NOTE;

		$header["contact-id"] = $contact["id"] ?? 0;

		if (!is_object($entries)) {
			Logger::info("There are no entries in this feed.");
			return [];
		}

		$items = [];

		// Limit the number of items that are about to be fetched
		$total_items = ($entries->length - 1);
		$max_items = DI::config()->get('system', 'max_feed_items');
		if (($max_items > 0) && ($total_items > $max_items)) {
			$total_items = $max_items;
		}

		// Importing older entries first
		for ($i = $total_items; $i >= 0; --$i) {
			$entry = $entries->item($i);

			$item = array_merge($header, $author);

			$alternate = XML::getFirstAttributes($xpath, "atom:link[@rel='alternate']", $entry);
			if (!is_object($alternate)) {
				$alternate = XML::getFirstAttributes($xpath, "atom:link", $entry);
			}
			if (is_object($alternate)) {
				foreach ($alternate AS $attribute) {
					if ($attribute->name == "href") {
						$item["plink"] = $attribute->textContent;
					}
				}
			}

			if (empty($item["plink"])) {
				$item["plink"] = XML::getFirstNodeValue($xpath, 'link/text()', $entry);
			}

			if (empty($item["plink"])) {
				$item["plink"] = XML::getFirstNodeValue($xpath, 'rss:link/text()', $entry);
			}

			$item["uri"] = XML::getFirstNodeValue($xpath, 'atom:id/text()', $entry);

			if (empty($item["uri"])) {
				$item["uri"] = XML::getFirstNodeValue($xpath, 'guid/text()', $entry);
			}

			if (empty($item["uri"])) {
				$item["uri"] = $item["plink"];
			}

			// Add the base path if missing
			$item["uri"] = Network::addBasePath($item["uri"], $basepath);
			$item["plink"] = Network::addBasePath($item["plink"], $basepath);

			$orig_plink = $item["plink"];

			$item["plink"] = DI::httpRequest()->finalUrl($item["plink"]);

			$item["parent-uri"] = $item["uri"];

			if (!$dryRun) {
				$condition = ["`uid` = ? AND `uri` = ? AND `network` IN (?, ?)",
					$importer["uid"], $item["uri"], Protocol::FEED, Protocol::DFRN];
				$previous = Item::selectFirst(['id'], $condition);
				if (DBA::isResult($previous)) {
					Logger::info("Item with uri " . $item["uri"] . " for user " . $importer["uid"] . " already existed under id " . $previous["id"]);
					continue;
				}
			}

			$item["title"] = XML::getFirstNodeValue($xpath, 'atom:title/text()', $entry);

			if (empty($item["title"])) {
				$item["title"] = XML::getFirstNodeValue($xpath, 'title/text()', $entry);
			}
			if (empty($item["title"])) {
				$item["title"] = XML::getFirstNodeValue($xpath, 'rss:title/text()', $entry);
			}

			$item["title"] = html_entity_decode($item["title"], ENT_QUOTES, 'UTF-8');

			$published = XML::getFirstNodeValue($xpath, 'atom:published/text()', $entry);

			if (empty($published)) {
				$published = XML::getFirstNodeValue($xpath, 'pubDate/text()', $entry);
			}

			if (empty($published)) {
				$published = XML::getFirstNodeValue($xpath, 'dc:date/text()', $entry);
			}

			$updated = XML::getFirstNodeValue($xpath, 'atom:updated/text()', $entry);

			if (empty($updated) && !empty($published)) {
				$updated = $published;
			}

			if (empty($published) && !empty($updated)) {
				$published = $updated;
			}

			if ($published != "") {
				$item["created"] = $published;
			}

			if ($updated != "") {
				$item["edited"] = $updated;
			}

			$creator = XML::getFirstNodeValue($xpath, 'author/text()', $entry);

			if (empty($creator)) {
				$creator = XML::getFirstNodeValue($xpath, 'atom:author/atom:name/text()', $entry);
			}

			if (empty($creator)) {
				$creator = XML::getFirstNodeValue($xpath, 'dc:creator/text()', $entry);
			}

			if ($creator != "") {
				$item["author-name"] = $creator;
			}

			$creator = XML::getFirstNodeValue($xpath, 'dc:creator/text()', $entry);

			if ($creator != "") {
				$item["author-name"] = $creator;
			}

			/// @TODO ?
			// <category>Ausland</category>
			// <media:thumbnail width="152" height="76" url="http://www.taz.de/picture/667875/192/14388767.jpg"/>

			$attachments = [];

			$enclosures = $xpath->query("enclosure|atom:link[@rel='enclosure']", $entry);
			foreach ($enclosures AS $enclosure) {
				$href = "";
				$length = "";
				$type = "";

				foreach ($enclosure->attributes AS $attribute) {
					if (in_array($attribute->name, ["url", "href"])) {
						$href = $attribute->textContent;
					} elseif ($attribute->name == "length") {
						$length = $attribute->textContent;
					} elseif ($attribute->name == "type") {
						$type = $attribute->textContent;
					}
				}

				if (!empty($item["attach"])) {
					$item["attach"] .= ',';
				} else {
					$item["attach"] = '';
				}

				$attachments[] = ["link" => $href, "type" => $type, "length" => $length];

				$item["attach"] .= '[attach]href="' . $href . '" length="' . $length . '" type="' . $type . '"[/attach]';
			}

			$taglist = [];
			$categories = $xpath->query("category", $entry);
			foreach ($categories AS $category) {
				$taglist[] = $category->nodeValue;
			}

			$body = trim(XML::getFirstNodeValue($xpath, 'atom:content/text()', $entry));

			if (empty($body)) {
				$body = trim(XML::getFirstNodeValue($xpath, 'content:encoded/text()', $entry));
			}

			$summary = trim(XML::getFirstNodeValue($xpath, 'atom:summary/text()', $entry));

			if (empty($summary)) {
				$summary = trim(XML::getFirstNodeValue($xpath, 'description/text()', $entry));
			}

			if (empty($body)) {
				$body = $summary;
				$summary = '';
			}

			if ($body == $summary) {
				$summary = '';
			}

			// remove the content of the title if it is identically to the body
			// This helps with auto generated titles e.g. from tumblr
			if (self::titleIsBody($item["title"], $body)) {
				$item["title"] = "";
			}
			$item["body"] = HTML::toBBCode($body, $basepath);

			if (($item["body"] == '') && ($item["title"] != '')) {
				$item["body"] = $item["title"];
				$item["title"] = '';
			}

			$preview = '';
			if (!empty($contact["fetch_further_information"]) && ($contact["fetch_further_information"] < 3)) {
				// Handle enclosures and treat them as preview picture
				foreach ($attachments AS $attachment) {
					if ($attachment["type"] == "image/jpeg") {
						$preview = $attachment["link"];
					}
				}

				// Remove a possible link to the item itself
				$item["body"] = str_replace($item["plink"], '', $item["body"]);
				$item["body"] = trim(preg_replace('/\[url\=\](\w+.*?)\[\/url\]/i', '', $item["body"]));

				// Replace the content when the title is longer than the body
				$replace = (strlen($item["title"]) > strlen($item["body"]));

				// Replace it, when there is an image in the body
				if (strstr($item["body"], '[/img]')) {
					$replace = true;
				}

				// Replace it, when there is a link in the body
				if (strstr($item["body"], '[/url]')) {
					$replace = true;
				}

				if ($replace) {
					$item["body"] = trim($item["title"]);
				}

				$data = ParseUrl::getSiteinfoCached($item['plink'], true);
				if (!empty($data['text']) && !empty($data['title']) && (mb_strlen($item['body']) < mb_strlen($data['text']))) {
					// When the fetched page info text is longer than the body, we do try to enhance the body
					if (!empty($item['body']) && (strpos($data['title'], $item['body']) === false) && (strpos($data['text'], $item['body']) === false)) {
						// The body is not part of the fetched page info title or page info text. So we add the text to the body
						$item['body'] .= "\n\n" . $data['text'];
					} else {
						// Else we replace the body with the page info text
						$item['body'] = $data['text'];
					}
				}

				// We always strip the title since it will be added in the page information
				$item["title"] = "";
				$item["body"] = $item["body"] . "\n" . PageInfo::getFooterFromUrl($item["plink"], false, $preview, ($contact["fetch_further_information"] == 2), $contact["ffi_keyword_denylist"] ?? '');
				$taglist = $contact["fetch_further_information"] == 2 ? PageInfo::getTagsFromUrl($item["plink"], $preview, $contact["ffi_keyword_denylist"] ?? '') : [];
				$item["object-type"] = Activity\ObjectType::BOOKMARK;
				unset($item["attach"]);
			} else {
				if (!empty($summary)) {
					$item["body"] = '[abstract]' . HTML::toBBCode($summary, $basepath) . "[/abstract]\n" . $item["body"];
				}

				if (!empty($contact["fetch_further_information"]) && ($contact["fetch_further_information"] == 3)) {
					if (empty($taglist)) {
						$taglist = PageInfo::getTagsFromUrl($item["plink"], $preview, $contact["ffi_keyword_denylist"] ?? '');
					}
					$item["body"] .= "\n" . self::tagToString($taglist);
				} else {
					$taglist = [];
				}

				// Add the link to the original feed entry if not present in feed
				if (($item['plink'] != '') && !strstr($item["body"], $item['plink'])) {
					$item["body"] .= "[hr][url]" . $item['plink'] . "[/url]";
				}
			}

			if ($dryRun) {
				$items[] = $item;
				break;
			} else {
				Logger::info('Stored feed', ['item' => $item]);

				$notify = Item::isRemoteSelf($contact, $item);

				// Distributed items should have a well formatted URI.
				// Additionally we have to avoid conflicts with identical URI between imported feeds and these items.
				if ($notify) {
					$item['guid'] = Item::guidFromUri($orig_plink, DI::baseUrl()->getHostname());
					unset($item['uri']);
					unset($item['parent-uri']);

					// Set the delivery priority for "remote self" to "medium"
					$notify = PRIORITY_MEDIUM;
				}

				$id = Item::insert($item, $notify);

				Logger::info("Feed for contact " . $contact["url"] . " stored under id " . $id);

				if (!empty($id) && !empty($taglist)) {
					$feeditem = Item::selectFirst(['uri-id'], ['id' => $id]);
					foreach ($taglist as $tag) {
						Tag::store($feeditem['uri-id'], Tag::HASHTAG, $tag);
					}					
				}
			}
		}

		return ["header" => $author, "items" => $items];
	}

	/**
	 * Convert a tag array to a tag string
	 *
	 * @param array $tags
	 * @return string tag string
	 */
	private static function tagToString(array $tags)
	{
		$tagstr = '';

		foreach ($tags as $tag) {
			if ($tagstr != "") {
				$tagstr .= ", ";
			}
	
			$tagstr .= "#[url=" . DI::baseUrl() . "/search?tag=" . urlencode($tag) . "]" . $tag . "[/url]";
		}

		return $tagstr;
	}

	private static function titleIsBody($title, $body)
	{
		$title = strip_tags($title);
		$title = trim($title);
		$title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
		$title = str_replace(["\n", "\r", "\t", " "], ["", "", "", ""], $title);

		$body = strip_tags($body);
		$body = trim($body);
		$body = html_entity_decode($body, ENT_QUOTES, 'UTF-8');
		$body = str_replace(["\n", "\r", "\t", " "], ["", "", "", ""], $body);

		if (strlen($title) < strlen($body)) {
			$body = substr($body, 0, strlen($title));
		}

		if (($title != $body) && (substr($title, -3) == "...")) {
			$pos = strrpos($title, "...");
			if ($pos > 0) {
				$title = substr($title, 0, $pos);
				$body = substr($body, 0, $pos);
			}
		}
		return ($title == $body);
	}

	/**
	 * Creates the Atom feed for a given nickname
	 *
	 * Supported filters:
	 * - activity (default): all the public posts
	 * - posts: all the public top-level posts
	 * - comments: all the public replies
	 *
	 * Updates the provided last_update parameter if the result comes from the
	 * cache or it is empty
	 *
	 * @param string  $owner_nick  Nickname of the feed owner
	 * @param string  $last_update Date of the last update
	 * @param integer $max_items   Number of maximum items to fetch
	 * @param string  $filter      Feed items filter (activity, posts or comments)
	 * @param boolean $nocache     Wether to bypass caching
	 *
	 * @return string Atom feed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function atom($owner_nick, $last_update, $max_items = 300, $filter = 'activity', $nocache = false)
	{
		$stamp = microtime(true);

		$owner = User::getOwnerDataByNick($owner_nick);
		if (!$owner) {
			return;
		}

		$cachekey = "feed:feed:" . $owner_nick . ":" . $filter . ":" . $last_update;

		$previous_created = $last_update;

		// Don't cache when the last item was posted less then 15 minutes ago (Cache duration)
		if ((time() - strtotime($owner['last-item'])) < 15*60) {
			$result = DI::cache()->get($cachekey);
			if (!$nocache && !is_null($result)) {
				Logger::info('Cached feed duration', ['seconds' => number_format(microtime(true) - $stamp, 3), 'nick' => $owner_nick, 'filter' => $filter, 'created' => $previous_created]);
				return $result['feed'];
			}
		}

		$check_date = empty($last_update) ? '' : DateTimeFormat::utc($last_update);
		$authorid = Contact::getIdForURL($owner["url"], 0, false);

		$condition = ["`uid` = ? AND `received` > ? AND NOT `deleted` AND `gravity` IN (?, ?)
			AND `private` != ? AND `visible` AND `wall` AND `parent-network` IN (?, ?, ?, ?)",
			$owner["uid"], $check_date, GRAVITY_PARENT, GRAVITY_COMMENT,
			Item::PRIVATE, Protocol::ACTIVITYPUB,
			Protocol::OSTATUS, Protocol::DFRN, Protocol::DIASPORA];

		if ($filter === 'comments') {
			$condition[0] .= " AND `object-type` = ? ";
			$condition[] = Activity\ObjectType::COMMENT;
		}

		if ($owner['account-type'] != User::ACCOUNT_TYPE_COMMUNITY) {
			$condition[0] .= " AND `contact-id` = ? AND `author-id` = ?";
			$condition[] = $owner["id"];
			$condition[] = $authorid;
		}

		$params = ['order' => ['received' => true], 'limit' => $max_items];

		if ($filter === 'posts') {
			$ret = Item::selectThread([], $condition, $params);
		} else {
			$ret = Item::select([], $condition, $params);
		}

		$items = Item::inArray($ret);

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::addHeader($doc, $owner, $filter);

		foreach ($items as $item) {
			$entry = self::entry($doc, $item, $owner);
			$root->appendChild($entry);

			if ($last_update < $item['created']) {
				$last_update = $item['created'];
			}
		}

		$feeddata = trim($doc->saveXML());

		$msg = ['feed' => $feeddata, 'last_update' => $last_update];
		DI::cache()->set($cachekey, $msg, Duration::QUARTER_HOUR);

		Logger::info('Feed duration', ['seconds' => number_format(microtime(true) - $stamp, 3), 'nick' => $owner_nick, 'filter' => $filter, 'created' => $previous_created]);

		return $feeddata;
	}

	/**
	 * Adds the header elements to the XML document
	 *
	 * @param DOMDocument $doc       XML document
	 * @param array       $owner     Contact data of the poster
	 * @param string      $filter    The related feed filter (activity, posts or comments)
	 *
	 * @return object header root element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function addHeader(DOMDocument $doc, array $owner, $filter)
	{
		$root = $doc->createElementNS(ActivityNamespace::ATOM1, 'feed');
		$doc->appendChild($root);

		$title = '';
		$selfUri = '/feed/' . $owner["nick"] . '/';
		switch ($filter) {
			case 'activity':
				$title = DI::l10n()->t('%s\'s timeline', $owner['name']);
				$selfUri .= $filter;
				break;
			case 'posts':
				$title = DI::l10n()->t('%s\'s posts', $owner['name']);
				break;
			case 'comments':
				$title = DI::l10n()->t('%s\'s comments', $owner['name']);
				$selfUri .= $filter;
				break;
		}

		$attributes = ["uri" => "https://friendi.ca", "version" => FRIENDICA_VERSION . "-" . DB_UPDATE_VERSION];
		XML::addElement($doc, $root, "generator", FRIENDICA_PLATFORM, $attributes);
		XML::addElement($doc, $root, "id", DI::baseUrl() . "/profile/" . $owner["nick"]);
		XML::addElement($doc, $root, "title", $title);
		XML::addElement($doc, $root, "subtitle", sprintf("Updates from %s on %s", $owner["name"], DI::config()->get('config', 'sitename')));
		XML::addElement($doc, $root, "logo", $owner["photo"]);
		XML::addElement($doc, $root, "updated", DateTimeFormat::utcNow(DateTimeFormat::ATOM));

		$author = self::addAuthor($doc, $owner);
		$root->appendChild($author);

		$attributes = ["href" => $owner["url"], "rel" => "alternate", "type" => "text/html"];
		XML::addElement($doc, $root, "link", "", $attributes);

		OStatus::hublinks($doc, $root, $owner["nick"]);

		$attributes = ["href" => DI::baseUrl() . $selfUri, "rel" => "self", "type" => "application/atom+xml"];
		XML::addElement($doc, $root, "link", "", $attributes);

		return $root;
	}

	/**
	 * Adds the author element to the XML document
	 *
	 * @param DOMDocument $doc          XML document
	 * @param array       $owner        Contact data of the poster
	 *
	 * @return \DOMElement author element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function addAuthor(DOMDocument $doc, array $owner)
	{
		$author = $doc->createElement("author");
		XML::addElement($doc, $author, "uri", $owner["url"]);
		XML::addElement($doc, $author, "name", $owner["nick"]);
		XML::addElement($doc, $author, "email", $owner["addr"]);

		return $author;
	}

	/**
	 * Adds an entry element to the XML document
	 *
	 * @param DOMDocument $doc       XML document
	 * @param array       $item      Data of the item that is to be posted
	 * @param array       $owner     Contact data of the poster
	 * @param bool        $toplevel  optional default false
	 *
	 * @return \DOMElement Entry element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function entry(DOMDocument $doc, array $item, array $owner)
	{
		$xml = null;

		$repeated_guid = OStatus::getResharedGuid($item);
		if ($repeated_guid != "") {
			$xml = self::reshareEntry($doc, $item, $owner, $repeated_guid);
		}

		if ($xml) {
			return $xml;
		}

		return self::noteEntry($doc, $item, $owner);
	}

		/**
	 * Adds an entry element with reshared content
	 *
	 * @param DOMDocument $doc           XML document
	 * @param array       $item          Data of the item that is to be posted
	 * @param array       $owner         Contact data of the poster
	 * @param string      $repeated_guid guid
	 * @param bool        $toplevel      Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return bool Entry element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function reshareEntry(DOMDocument $doc, array $item, array $owner, $repeated_guid)
	{
		if (($item['gravity'] != GRAVITY_PARENT) && (Strings::normaliseLink($item["author-link"]) != Strings::normaliseLink($owner["url"]))) {
			Logger::info('Feed entry author does not match feed owner', ['owner' => $owner["url"], 'author' => $item["author-link"]]);
		}

		$entry = OStatus::entryHeader($doc, $owner, $item, false);

		$condition = ['uid' => $owner["uid"], 'guid' => $repeated_guid, 'private' => [Item::PUBLIC, Item::UNLISTED],
			'network' => Protocol::FEDERATED];
		$repeated_item = Item::selectFirst([], $condition);
		if (!DBA::isResult($repeated_item)) {
			return false;
		}

		self::entryContent($doc, $entry, $item, self::getTitle($repeated_item), Activity::SHARE, false);

		self::entryFooter($doc, $entry, $item, $owner);

		return $entry;
	}

	/**
	 * Adds a regular entry element
	 *
	 * @param DOMDocument $doc       XML document
	 * @param array       $item      Data of the item that is to be posted
	 * @param array       $owner     Contact data of the poster
	 * @param bool        $toplevel  Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return \DOMElement Entry element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function noteEntry(DOMDocument $doc, array $item, array $owner)
	{
		if (($item['gravity'] != GRAVITY_PARENT) && (Strings::normaliseLink($item["author-link"]) != Strings::normaliseLink($owner["url"]))) {
			Logger::info('Feed entry author does not match feed owner', ['owner' => $owner["url"], 'author' => $item["author-link"]]);
		}

		$entry = OStatus::entryHeader($doc, $owner, $item, false);

		self::entryContent($doc, $entry, $item, self::getTitle($item), '', true);

		self::entryFooter($doc, $entry, $item, $owner);

		return $entry;
	}

	/**
	 * Adds elements to the XML document
	 *
	 * @param DOMDocument $doc       XML document
	 * @param \DOMElement $entry     Entry element where the content is added
	 * @param array       $item      Data of the item that is to be posted
	 * @param array       $owner     Contact data of the poster
	 * @param string      $title     Title for the post
	 * @param string      $verb      The activity verb
	 * @param bool        $complete  Add the "status_net" element?
	 * @param bool        $feed_mode Behave like a regular feed for users if true
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function entryContent(DOMDocument $doc, \DOMElement $entry, array $item, $title, $verb = "", $complete = true)
	{
		if ($verb == "") {
			$verb = OStatus::constructVerb($item);
		}

		XML::addElement($doc, $entry, "id", $item["uri"]);
		XML::addElement($doc, $entry, "title", html_entity_decode($title, ENT_QUOTES, 'UTF-8'));

		$body = OStatus::formatPicturePost($item['body']);

		$body = BBCode::convert($body, false, BBCode::OSTATUS);

		XML::addElement($doc, $entry, "content", $body, ["type" => "html"]);

		XML::addElement($doc, $entry, "link", "", ["rel" => "alternate", "type" => "text/html",
								"href" => DI::baseUrl()."/display/".$item["guid"]]
		);

		XML::addElement($doc, $entry, "published", DateTimeFormat::utc($item["created"]."+00:00", DateTimeFormat::ATOM));
		XML::addElement($doc, $entry, "updated", DateTimeFormat::utc($item["edited"]."+00:00", DateTimeFormat::ATOM));
	}

	/**
	 * Adds the elements at the foot of an entry to the XML document
	 *
	 * @param DOMDocument $doc       XML document
	 * @param object      $entry     The entry element where the elements are added
	 * @param array       $item      Data of the item that is to be posted
	 * @param array       $owner     Contact data of the poster
	 * @param bool        $complete  default true
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function entryFooter(DOMDocument $doc, $entry, array $item, array $owner)
	{
		$mentioned = [];

		if ($item['gravity'] != GRAVITY_PARENT) {
			$parent = Item::selectFirst(['guid', 'author-link', 'owner-link'], ['id' => $item['parent']]);
			$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);

			$thrparent = Item::selectFirst(['guid', 'author-link', 'owner-link', 'plink'], ['uid' => $owner["uid"], 'uri' => $parent_item]);

			if (DBA::isResult($thrparent)) {
				$mentioned[$thrparent["author-link"]] = $thrparent["author-link"];
				$mentioned[$thrparent["owner-link"]] = $thrparent["owner-link"];
				$parent_plink = $thrparent["plink"];
			} else {
				$mentioned[$parent["author-link"]] = $parent["author-link"];
				$mentioned[$parent["owner-link"]] = $parent["owner-link"];
				$parent_plink = DI::baseUrl()."/display/".$parent["guid"];
			}

			$attributes = [
					"ref" => $parent_item,
					"href" => $parent_plink];
			XML::addElement($doc, $entry, "thr:in-reply-to", "", $attributes);

			$attributes = [
					"rel" => "related",
					"href" => $parent_plink];
			XML::addElement($doc, $entry, "link", "", $attributes);
		}

		// uri-id isn't present for follow entry pseudo-items
		$tags = Tag::getByURIId($item['uri-id'] ?? 0);
		foreach ($tags as $tag) {
			$mentioned[$tag['url']] = $tag['url'];
		}

		foreach ($tags as $tag) {
			if ($tag['type'] == Tag::HASHTAG) {
				XML::addElement($doc, $entry, "category", "", ["term" => $tag['name']]);
			}
		}

		OStatus::getAttachment($doc, $entry, $item);
	}

	/**
	 * Fetch or create title for feed entry
	 *
	 * @param array $item
	 * @return string title
	 */
	private static function getTitle(array $item)
	{
		if ($item['title'] != '') {
			return BBCode::convert($item['title'], false, BBCode::OSTATUS);
		}

		// Fetch information about the post
		$siteinfo = BBCode::getAttachedData($item["body"]);
		if (isset($siteinfo["title"])) {
			return $siteinfo["title"];
		}

		// If no bookmark is found then take the first line
		// Remove the share element before fetching the first line
		$title = trim(preg_replace("/\[share.*?\](.*?)\[\/share\]/ism","\n$1\n",$item['body']));

		$title = HTML::toPlaintext(BBCode::convert($title, false), 0, true)."\n";
		$pos = strpos($title, "\n");
		$trailer = "";
		if (($pos == 0) || ($pos > 100)) {
			$pos = 100;
			$trailer = "...";
		}

		return substr($title, 0, $pos) . $trailer;
	}
}
