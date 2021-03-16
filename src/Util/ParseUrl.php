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

namespace Friendica\Util;

use DOMDocument;
use DOMXPath;
use Friendica\Content\OEmbed;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * Get information about a given URL
 *
 * Class with methods for extracting certain content from an url
 */
class ParseUrl
{
	const DEFAULT_EXPIRATION_FAILURE = 'now + 1 day';
	const DEFAULT_EXPIRATION_SUCCESS = 'now + 3 months';

	/**
	 * Maximum number of characters for the description
	 */
	const MAX_DESC_COUNT = 250;

	/**
	 * Minimum number of characters for the description
	 */
	const MIN_DESC_COUNT = 100;

	/**
	 * Fetch the content type of the given url
	 * @param string $url URL of the page
	 * @return array content type 
	 */
	public static function getContentType(string $url)
	{
		$curlResult = DI::httpRequest()->head($url);
		if (!$curlResult->isSuccess()) {
			return [];
		}

		$contenttype =  $curlResult->getHeader('Content-Type');
		if (empty($contenttype)) {
			return [];
		}

		return explode('/', current(explode(';', $contenttype)));
	}

	/**
	 * Search for chached embeddable data of an url otherwise fetch it
	 *
	 * @param string $url         The url of the page which should be scraped
	 * @param bool   $no_guessing If true the parse doens't search for
	 *                            preview pictures
	 * @param bool   $do_oembed   The false option is used by the function fetch_oembed()
	 *                            to avoid endless loops
	 *
	 * @return array which contains needed data for embedding
	 *    string 'url'      => The url of the parsed page
	 *    string 'type'     => Content type
	 *    string 'title'    => (optional) The title of the content
	 *    string 'text'     => (optional) The description for the content
	 *    string 'image'    => (optional) A preview image of the content (only available if $no_geuessing = false)
	 *    array  'images'   => (optional) Array of preview pictures
	 *    string 'keywords' => (optional) The tags which belong to the content
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @see   ParseUrl::getSiteinfo() for more information about scraping
	 * embeddable content
	 */
	public static function getSiteinfoCached($url, $no_guessing = false, $do_oembed = true): array
	{
		if (empty($url)) {
			return [
				'url' => '',
				'type' => 'error',
			];
		}

		$urlHash = hash('sha256', $url);

		$parsed_url = DBA::selectFirst('parsed_url', ['content'],
			['url_hash' => $urlHash, 'guessing' => !$no_guessing, 'oembed' => $do_oembed]
		);
		if (!empty($parsed_url['content'])) {
			$data = unserialize($parsed_url['content']);
			return $data;
		}

		$data = self::getSiteinfo($url, $no_guessing, $do_oembed);

		$expires = $data['expires'];

		unset($data['expires']);

		DI::dba()->insert(
			'parsed_url',
			[
				'url_hash' => $urlHash,
				'guessing' => !$no_guessing,
				'oembed'   => $do_oembed,
				'url'      => $url,
				'content'  => serialize($data),
				'created'  => DateTimeFormat::utcNow(),
				'expires'  => $expires,
			],
			Database::INSERT_UPDATE
		);

		return $data;
	}

	/**
	 * Parse a page for embeddable content information
	 *
	 * This method parses to url for meta data which can be used to embed
	 * the content. If available it prioritizes Open Graph meta tags.
	 * If this is not available it uses the twitter cards meta tags.
	 * As fallback it uses standard html elements with meta informations
	 * like \<title\>Awesome Title\</title\> or
	 * \<meta name="description" content="An awesome description"\>
	 *
	 * @param string $url         The url of the page which should be scraped
	 * @param bool   $no_guessing If true the parse doens't search for
	 *                            preview pictures
	 * @param bool   $do_oembed   The false option is used by the function fetch_oembed()
	 *                            to avoid endless loops
	 * @param int    $count       Internal counter to avoid endless loops
	 *
	 * @return array which contains needed data for embedding
	 *    string 'url'      => The url of the parsed page
	 *    string 'type'     => Content type (error, link, photo, image, audio, video)
	 *    string 'title'    => (optional) The title of the content
	 *    string 'text'     => (optional) The description for the content
	 *    string 'image'    => (optional) A preview image of the content (only available if $no_guessing = false)
	 *    array  'images'   => (optional) Array of preview pictures
	 *    string 'keywords' => (optional) The tags which belong to the content
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  https://developers.google.com/+/plugins/snippet/
	 * @verbatim
	 * <meta itemprop="name" content="Awesome title">
	 * <meta itemprop="description" content="An awesome description">
	 * <meta itemprop="image" content="http://maple.libertreeproject.org/images/tree-icon.png">
	 *
	 * <body itemscope itemtype="http://schema.org/Product">
	 *   <h1 itemprop="name">Shiny Trinket</h1>
	 *   <img itemprop="image" src="{image-url}" />
	 *   <p itemprop="description">Shiny trinkets are shiny.</p>
	 * </body>
	 * @endverbatim
	 */
	public static function getSiteinfo($url, $no_guessing = false, $do_oembed = true, $count = 1)
	{
		if (empty($url)) {
			return [
				'url' => '',
				'type' => 'error',
			];
		}

		// Check if the URL does contain a scheme
		$scheme = parse_url($url, PHP_URL_SCHEME);

		if ($scheme == '') {
			$url = 'http://' . ltrim($url, '/');
		}

		$url = trim($url, "'\"");

		$url = Network::stripTrackingQueryParams($url);

		$siteinfo = [
			'url' => $url,
			'type' => 'link',
			'expires' => DateTimeFormat::utc(self::DEFAULT_EXPIRATION_FAILURE),
		];

		if ($count > 10) {
			Logger::log('Endless loop detected for ' . $url, Logger::DEBUG);
			return $siteinfo;
		}

		$type = self::getContentType($url);
		Logger::info('Got content-type', ['content-type' => $type, 'url' => $url]);
		if (!empty($type) && in_array($type[0], ['image', 'video', 'audio'])) {
			$siteinfo['type'] = $type[0];
			return $siteinfo;
		}

		if ((count($type) >= 2) && (($type[0] != 'text') || ($type[1] != 'html'))) {
			Logger::info('Unparseable content-type, quitting here, ', ['content-type' => $type, 'url' => $url]);
			return $siteinfo;
		}

		$curlResult = DI::httpRequest()->get($url);
		if (!$curlResult->isSuccess()) {
			return $siteinfo;
		}

		$siteinfo['expires'] = DateTimeFormat::utc(self::DEFAULT_EXPIRATION_SUCCESS);

		// If the file is too large then exit
		if (($curlResult->getInfo()['download_content_length'] ?? 0) > 1000000) {
			return $siteinfo;
		}

		if ($cacheControlHeader = $curlResult->getHeader('Cache-Control')) {
			if (preg_match('/max-age=([0-9]+)/i', $cacheControlHeader, $matches)) {
				$maxAge = max(86400, (int)array_pop($matches));
				$siteinfo['expires'] = DateTimeFormat::utc("now + $maxAge seconds");
			}
		}

		$header = $curlResult->getHeader();
		$body = $curlResult->getBody();

		if ($do_oembed) {
			$oembed_data = OEmbed::fetchURL($url, false, false);

			if (!empty($oembed_data->type)) {
				if (!in_array($oembed_data->type, ['error', 'rich', 'image', 'video', 'audio', ''])) {
					$siteinfo['type'] = $oembed_data->type;
				}

				// See https://github.com/friendica/friendica/pull/5763#discussion_r217913178
				if ($siteinfo['type'] != 'photo') {
					if (!empty($oembed_data->title)) {
						$siteinfo['title'] = trim($oembed_data->title);
					}
					if (!empty($oembed_data->description)) {
						$siteinfo['text'] = trim($oembed_data->description);
					}
					if (!empty($oembed_data->author_name)) {
						$siteinfo['author_name'] = trim($oembed_data->author_name);
					}
					if (!empty($oembed_data->author_url)) {
						$siteinfo['author_url'] = trim($oembed_data->author_url);
					}
					if (!empty($oembed_data->provider_name)) {
						$siteinfo['publisher_name'] = trim($oembed_data->provider_name);
					}
					if (!empty($oembed_data->provider_url)) {
						$siteinfo['publisher_url'] = trim($oembed_data->provider_url);
					}
					if (!empty($oembed_data->thumbnail_url)) {
						$siteinfo['image'] = $oembed_data->thumbnail_url;
					}
				}
			}
		}

		$charset = '';
		// Look for a charset, first in headers
		// Expected form: Content-Type: text/html; charset=ISO-8859-4
		if (preg_match('/charset=([a-z0-9-_.\/]+)/i', $header, $matches)) {
			$charset = trim(trim(trim(array_pop($matches)), ';,'));
		}

		// Then in body that gets precedence
		// Expected forms:
		// - <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		// - <meta charset="utf-8">
		// - <meta charset=utf-8>
		// - <meta charSet="utf-8">
		// We escape <style> and <script> tags since they can contain irrelevant charset information
		// (see https://github.com/friendica/friendica/issues/9251#issuecomment-698636806)
		Strings::performWithEscapedBlocks($body, '#<(?:style|script).*?</(?:style|script)>#ism', function ($body) use (&$charset) {
			if (preg_match('/charset=["\']?([a-z0-9-_.\/]+)/i', $body, $matches)) {
				$charset = trim(trim(trim(array_pop($matches)), ';,'));
			}
		});

		$siteinfo['charset'] = $charset;

		if ($charset && strtoupper($charset) != 'UTF-8') {
			// See https://github.com/friendica/friendica/issues/5470#issuecomment-418351211
			$charset = str_ireplace('latin-1', 'latin1', $charset);

			Logger::log('detected charset ' . $charset, Logger::DEBUG);
			$body = iconv($charset, 'UTF-8//TRANSLIT', $body);
		}

		$body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');

		$doc = new DOMDocument();
		@$doc->loadHTML($body);

		XML::deleteNode($doc, 'style');
		XML::deleteNode($doc, 'option');
		XML::deleteNode($doc, 'h1');
		XML::deleteNode($doc, 'h2');
		XML::deleteNode($doc, 'h3');
		XML::deleteNode($doc, 'h4');
		XML::deleteNode($doc, 'h5');
		XML::deleteNode($doc, 'h6');
		XML::deleteNode($doc, 'ol');
		XML::deleteNode($doc, 'ul');

		$xpath = new DOMXPath($doc);

		$list = $xpath->query('//meta[@content]');
		foreach ($list as $node) {
			$meta_tag = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$meta_tag[$attribute->name] = $attribute->value;
				}
			}

			if (@$meta_tag['http-equiv'] == 'refresh') {
				$path = $meta_tag['content'];
				$pathinfo = explode(';', $path);
				$content = '';
				foreach ($pathinfo as $value) {
					if (substr(strtolower($value), 0, 4) == 'url=') {
						$content = substr($value, 4);
					}
				}
				if ($content != '') {
					$siteinfo = self::getSiteinfo($content, $no_guessing, $do_oembed, ++$count);
					return $siteinfo;
				}
			}
		}

		$list = $xpath->query('//title');
		if ($list->length > 0) {
			$siteinfo['title'] = trim($list->item(0)->nodeValue);
		}

		$list = $xpath->query('//meta[@name]');
		foreach ($list as $node) {
			$meta_tag = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$meta_tag[$attribute->name] = $attribute->value;
				}
			}

			if (empty($meta_tag['content'])) {
				continue;
			}

			$meta_tag['content'] = trim(html_entity_decode($meta_tag['content'], ENT_QUOTES, 'UTF-8'));

			switch (strtolower($meta_tag['name'])) {
				case 'fulltitle':
					$siteinfo['title'] = trim($meta_tag['content']);
					break;
				case 'description':
					$siteinfo['text'] = trim($meta_tag['content']);
					break;
				case 'thumbnail':
					$siteinfo['image'] = $meta_tag['content'];
					break;
				case 'twitter:image':
					$siteinfo['image'] = $meta_tag['content'];
					break;
				case 'twitter:image:src':
					$siteinfo['image'] = $meta_tag['content'];
					break;
				case 'twitter:card':
					// Detect photo pages
					if ($meta_tag['content'] == 'summary_large_image') {
						$siteinfo['type'] = 'photo';
					}
					break;
				case 'twitter:description':
					$siteinfo['text'] = trim($meta_tag['content']);
					break;
				case 'twitter:title':
					$siteinfo['title'] = trim($meta_tag['content']);
					break;
				case 'dc.title':
					$siteinfo['title'] = trim($meta_tag['content']);
					break;
				case 'dc.description':
					$siteinfo['text'] = trim($meta_tag['content']);
					break;
				case 'dc.creator':
					$siteinfo['publisher_name'] = trim($meta_tag['content']);
					break;
				case 'keywords':
					$keywords = explode(',', $meta_tag['content']);
					break;
				case 'news_keywords':
					$keywords = explode(',', $meta_tag['content']);
					break;
			}
		}

		if (isset($keywords)) {
			$siteinfo['keywords'] = [];
			foreach ($keywords as $keyword) {
				if (!in_array(trim($keyword), $siteinfo['keywords'])) {
					$siteinfo['keywords'][] = trim($keyword);
				}
			}
		}

		$list = $xpath->query('//meta[@property]');
		foreach ($list as $node) {
			$meta_tag = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$meta_tag[$attribute->name] = $attribute->value;
				}
			}

			if (!empty($meta_tag['content'])) {
				$meta_tag['content'] = trim(html_entity_decode($meta_tag['content'], ENT_QUOTES, 'UTF-8'));

				switch (strtolower($meta_tag['property'])) {
					case 'og:image':
						$siteinfo['image'] = $meta_tag['content'];
						break;
					case 'og:image:url':
						$siteinfo['image'] = $meta_tag['content'];
						break;
					case 'og:image:secure_url':
						$siteinfo['image'] = $meta_tag['content'];
						break;
					case 'og:title':
						$siteinfo['title'] = trim($meta_tag['content']);
						break;
					case 'og:description':
						$siteinfo['text'] = trim($meta_tag['content']);
						break;
					case 'og:site_name':
						$siteinfo['publisher_name'] = trim($meta_tag['content']);
						break;
					case 'twitter:description':
						$siteinfo['text'] = trim($meta_tag['content']);
						break;
					case 'twitter:title':
						$siteinfo['title'] = trim($meta_tag['content']);
						break;
					case 'twitter:image':
						$siteinfo['image'] = $meta_tag['content'];
						break;
				}
			}
		}

		$list = $xpath->query("//script[@type='application/ld+json']");
		foreach ($list as $node) {
			if (!empty($node->nodeValue)) {
				$nodevalue = html_entity_decode($node->nodeValue, ENT_COMPAT, 'UTF-8');
				if ($jsonld = json_decode($nodevalue, true)) {
					if (!empty($jsonld['@graph']) && is_array($jsonld['@graph'])) {
						foreach ($jsonld['@graph'] as $part) {
							$siteinfo = self::parseJsonLd($siteinfo, $part);
						}
					} else {
						$siteinfo = self::parseJsonLd($siteinfo, $jsonld);
					}
				}
			}
		}

		// Prevent to have a photo type without an image
		if ((empty($siteinfo['image']) || !empty($siteinfo['text'])) && ($siteinfo['type'] == 'photo')) {
			$siteinfo['type'] = 'link';
		}

		if (!empty($siteinfo['image'])) {
			$src = self::completeUrl($siteinfo['image'], $url);

			unset($siteinfo['image']);

			$photodata = Images::getInfoFromURLCached($src);

			if (($photodata) && ($photodata[0] > 10) && ($photodata[1] > 10)) {
				$siteinfo['images'][] = ['src' => $src,
					'width' => $photodata[0],
					'height' => $photodata[1]];
			}
		}

		if (!empty($siteinfo['text']) && mb_strlen($siteinfo['text']) > self::MAX_DESC_COUNT) {
			$siteinfo['text'] = mb_substr($siteinfo['text'], 0, self::MAX_DESC_COUNT) . '…';
			$pos = mb_strrpos($siteinfo['text'], '.');
			if ($pos > self::MIN_DESC_COUNT) {
				$siteinfo['text'] = mb_substr($siteinfo['text'], 0, $pos + 1);
			}
		}

		Logger::info('Siteinfo fetched', ['url' => $url, 'siteinfo' => $siteinfo]);

		Hook::callAll('getsiteinfo', $siteinfo);

		return $siteinfo;
	}

	/**
	 * Improve the siteinfo with information from the provided JSON-LD information
	 * @see https://jsonld.com/
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 * @return array siteinfo
	 */
	private static function parseJsonLd(array $siteinfo, array $jsonld)
	{
		$type = JsonLD::fetchElement($jsonld, '@type');

		switch ($type) {
			case 'Article':
			case 'NewsArticle':
				return self::parseJsonLdArticle($siteinfo, $jsonld);
			case 'WebPage':
				return self::parseJsonLdWebPage($siteinfo, $jsonld);
			case 'WebSite':
				return self::parseJsonLdWebSite($siteinfo, $jsonld);
			case 'Organization':
				return self::parseJsonLdWebOrganization($siteinfo, $jsonld);
			case 'Person':
				return self::parseJsonLdWebPerson($siteinfo, $jsonld);
			case 'BreadcrumbList':
			case 'Audio': /// @todo Can contain direct media links to audio - can be interesting in the future
			case 'VideoObject':
			case 'ImageObject':
			case 'LiveBlogPosting':
			case 'SocialMediaPosting':
					// quit silently
				return $siteinfo;
			default:
				Logger::info('Unsupported or unknown type', ['type' => $type, 'url' => $siteinfo['url']]);
				return $siteinfo;
		}
	}

	/**
	 * Improve the siteinfo with information from the provided JSON-LD information concerning authors and publishers
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 * @return array siteinfo
	 */
	private static function parseJsonLdAuthor(array $siteinfo, array $jsonld)
	{
		$jsonldinfo = [];

		if (!empty($jsonld['publisher']) && is_array($jsonld['publisher'])) {
			$content = JsonLD::fetchElement($jsonld, 'publisher', 'name', '@type', 'Organization');
			if (!empty($content) && is_string($content)) {
				$jsonldinfo['publisher_name'] = trim($content);
			}

			$content = JsonLD::fetchElement($jsonld, 'publisher', 'url', '@type', 'Organization');
			if (!empty($content) && is_string($content)) {
				$jsonldinfo['publisher_url'] = trim($content);
			}

			$brand = JsonLD::fetchElement($jsonld, 'publisher', 'brand', '@type', 'Organization');
			if (!empty($brand)) {
				$content = JsonLD::fetchElement($brand, 'name', '@type', 'brand');
				if (!empty($content) && is_string($content)) {
					$jsonldinfo['publisher_name'] = trim($content);
				}
			}
		}

		if (!empty($jsonld['author']) && is_array($jsonld['author'])) {
			$content = JsonLD::fetchElement($jsonld, 'author', 'name', '@type', 'Organization');
			if (!empty($content) && is_string($content)) {
				$jsonldinfo['publisher_name'] = trim($content);
			}

			$content = JsonLD::fetchElement($jsonld, 'author', 'url', '@type', 'Organization');
			if (!empty($content) && is_string($content)) {
				$jsonldinfo['publisher_url'] = trim($content);
			}

			$content = JsonLD::fetchElement($jsonld, 'author', 'name', '@type', 'Person');
			if (!empty($content) && is_string($content)) {
				$jsonldinfo['author_name'] = trim($content);
			}

			$content = JsonLD::fetchElement($jsonld, 'author', 'url', '@type', 'Person');
			if (!empty($content) && is_string($content)) {
				$jsonldinfo['author_url'] = trim($content);
			}
		}

		Logger::info('Fetched author information', ['fetched' => $jsonldinfo]);

		return array_merge($siteinfo, $jsonldinfo);
	}

	/**
	 * Improve the siteinfo with information from the provided JSON-LD Article information
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 * @return array siteinfo
	 */
	private static function parseJsonLdArticle(array $siteinfo, array $jsonld)
	{
		$jsonldinfo = [];

		$content = JsonLD::fetchElement($jsonld, 'headline');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['title'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'alternativeHeadline');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['alternative_title'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'description');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['text'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'image', 'url', '@type', 'ImageObject');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['image'] = trim($content);
		}

/// @todo Check for the correct separator, also check for dpublicates before adding
//		$content = JsonLD::fetchElement($jsonld, 'keywords');
//		if (!empty($content) && is_string($content)) {
//			$jsonldinfo['keywords'] = trim($content);
//		}

		$jsonldinfo = self::parseJsonLdAuthor($jsonldinfo, $jsonld);

		Logger::info('Fetched article information', ['url' => $siteinfo['url'], 'fetched' => $jsonldinfo]);

		return array_merge($siteinfo, $jsonldinfo);
	}

	/**
	 * Improve the siteinfo with information from the provided JSON-LD WebPage information
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 * @return array siteinfo
	 */
	private static function parseJsonLdWebPage(array $siteinfo, array $jsonld)
	{
		$jsonldinfo = [];

		$content = JsonLD::fetchElement($jsonld, 'name');
		if (!empty($content)) {
			$jsonldinfo['title'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'description');
		if (!empty($content)) {
			$jsonldinfo['text'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'thumbnailUrl');
		if (!empty($content)) {
			$jsonldinfo['image'] = trim($content);
		}

		$jsonldinfo = self::parseJsonLdAuthor($jsonldinfo, $jsonld);

		Logger::info('Fetched webpage information', ['url' => $siteinfo['url'], 'fetched' => $jsonldinfo]);

		return array_merge($siteinfo, $jsonldinfo);
	}

	/**
	 * Improve the siteinfo with information from the provided JSON-LD WebSite information
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 * @return array siteinfo
	 */
	private static function parseJsonLdWebSite(array $siteinfo, array $jsonld)
	{
		$jsonldinfo = [];

		$content = JsonLD::fetchElement($jsonld, 'name');
		if (!empty($content)) {
			$jsonldinfo['publisher_name'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'description');
		if (!empty($content)) {
			$jsonldinfo['publisher_description'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'url');
		if (!empty($content)) {
			$jsonldinfo['publisher_url'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'thumbnailUrl');
		if (!empty($content)) {
			$jsonldinfo['image'] = trim($content);
		}

		$jsonldinfo = self::parseJsonLdAuthor($jsonldinfo, $jsonld);

		Logger::info('Fetched WebSite information', ['url' => $siteinfo['url'], 'fetched' => $jsonldinfo]);
		return array_merge($siteinfo, $jsonldinfo);
	}

	/**
	 * Improve the siteinfo with information from the provided JSON-LD Organization information
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 * @return array siteinfo
	 */
	private static function parseJsonLdWebOrganization(array $siteinfo, array $jsonld)
	{
		$jsonldinfo = [];

		$content = JsonLD::fetchElement($jsonld, 'name');
		if (!empty($content)) {
			$jsonldinfo['publisher_name'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'url');
		if (!empty($content)) {
			$jsonldinfo['publisher_url'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'logo', 'url', '@type', 'ImageObject');
		if (!empty($content)) {
			$jsonldinfo['publisher_img'] = trim($content);
		}

		Logger::info('Fetched Organization information', ['url' => $siteinfo['url'], 'fetched' => $jsonldinfo]);
		return array_merge($siteinfo, $jsonldinfo);
	}

	/**
	 * Improve the siteinfo with information from the provided JSON-LD Person information
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 * @return array siteinfo
	 */
	private static function parseJsonLdWebPerson(array $siteinfo, array $jsonld)
	{
		$jsonldinfo = [];

		$content = JsonLD::fetchElement($jsonld, 'name');
		if (!empty($content)) {
			$jsonldinfo['author_name'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'description');
		if (!empty($content)) {
			$jsonldinfo['author_description'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'url');
		if (!empty($content)) {
			$jsonldinfo['author_url'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'image', 'url', '@type', 'ImageObject');
		if (!empty($content)) {
			$jsonldinfo['author_img'] = trim($content);
		}

		Logger::info('Fetched Person information', ['url' => $siteinfo['url'], 'fetched' => $jsonldinfo]);
		return array_merge($siteinfo, $jsonldinfo);
	}

	/**
	 * Convert tags from CSV to an array
	 *
	 * @param string $string Tags
	 * @return array with formatted Hashtags
	 */
	public static function convertTagsToArray($string)
	{
		$arr_tags = str_getcsv($string);
		if (count($arr_tags)) {
			// add the # sign to every tag
			array_walk($arr_tags, ["self", "arrAddHashes"]);

			return $arr_tags;
		}
	}

	/**
	 * Add a hasht sign to a string
	 *
	 * This method is used as callback function
	 *
	 * @param string $tag The pure tag name
	 * @param int    $k   Counter for internal use
	 * @return void
	 */
	private static function arrAddHashes(&$tag, $k)
	{
		$tag = "#" . $tag;
	}

	/**
	 * Add a scheme to an url
	 *
	 * The src attribute of some html elements (e.g. images)
	 * can miss the scheme so we need to add the correct
	 * scheme
	 *
	 * @param string $url    The url which possibly does have
	 *                       a missing scheme (a link to an image)
	 * @param string $scheme The url with a correct scheme
	 *                       (e.g. the url from the webpage which does contain the image)
	 *
	 * @return string The url with a scheme
	 */
	private static function completeUrl($url, $scheme)
	{
		$urlarr = parse_url($url);

		// If the url does allready have an scheme
		// we can stop the process here
		if (isset($urlarr["scheme"])) {
			return($url);
		}

		$schemearr = parse_url($scheme);

		$complete = $schemearr["scheme"]."://".$schemearr["host"];

		if (!empty($schemearr["port"])) {
			$complete .= ":".$schemearr["port"];
		}

		if (!empty($urlarr["path"])) {
			if (strpos($urlarr["path"], "/") !== 0) {
				$complete .= "/";
			}

			$complete .= $urlarr["path"];
		}

		if (!empty($urlarr["query"])) {
			$complete .= "?".$urlarr["query"];
		}

		if (!empty($urlarr["fragment"])) {
			$complete .= "#".$urlarr["fragment"];
		}

		return($complete);
	}
}
