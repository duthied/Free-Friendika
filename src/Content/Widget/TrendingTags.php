<?php

namespace Friendica\Content\Widget;

use Friendica\Core\Cache;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Model\Term;

class TrendingTags
{
	/**
	 * @param string $content 'global' (all posts) or 'local' (this node's posts only)
	 * @param int    $period  Period in hours to consider posts
	 * @return string
	 * @throws \Exception
	 */
	public static function getHTML($content = 'global', int $period = 24)
	{
		if ($content == 'local') {
			$tags = self::getLocalTrendingTags($period);
		} else {
			$tags = self::getGlobalTrendingTags($period);
		}

		$tpl = Renderer::getMarkupTemplate('widget/trending_tags.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$title' => L10n::tt('Trending Tags (last %d hour)', 'Trending Tags (last %d hours)', $period),
			'$more' => L10n::t('More Trending Tags'),
			'$tags' => $tags,
		]);

		return $o;
	}

	/**
	 * Returns a list of the most frequent global tags over the given period
	 *
	 * @param int $period Period in hours to consider posts
	 * @return array
	 * @throws \Exception
	 */
	private static function getGlobalTrendingTags(int $period)
	{
		$tags = Cache::get('global_trending_tags');

		if (!$tags) {
			$tagsStmt = DBA::p("SELECT t.`term`, COUNT(*) AS `score`
FROM `term` t
 JOIN `item` i ON i.`id` = t.`oid` AND i.`uid` = t.`uid`
 JOIN `thread` ON `thread`.`iid` = i.`id`
WHERE `thread`.`visible`
  AND NOT `thread`.`deleted`
  AND NOT `thread`.`moderated`
  AND NOT `thread`.`private`
  AND t.`uid` = 0
  AND t.`otype` = ?
  AND t.`type` = ?
  AND t.`term` != ''
  AND i.`received` > DATE_SUB(NOW(), INTERVAL ? HOUR)
GROUP BY `term`
ORDER BY `score` DESC
LIMIT 20", Term::OBJECT_TYPE_POST, Term::HASHTAG, $period);

			if (DBA::isResult($tags)) {
				$tags = DBA::toArray($tagsStmt);
				Cache::set('global_trending_tags', $tags, Cache::HOUR);
			}
		}

		return $tags ?: [];
	}

	/**
	 * Returns a list of the most frequent local tags over the given period
	 *
	 * @param int $period Period in hours to consider posts
	 * @return array
	 * @throws \Exception
	 */
	private static function getLocalTrendingTags(int $period)
	{
		$tags = Cache::get('local_trending_tags');

		if (!$tags) {
			$tagsStmt = DBA::p("SELECT t.`term`, COUNT(*) AS `score`
FROM `term` t
JOIN `item` i ON i.`id` = t.`oid` AND i.`uid` = t.`uid`
JOIN `thread` ON `thread`.`iid` = i.`id`
JOIN `user` ON `user`.`uid` = `thread`.`uid` AND NOT `user`.`hidewall`
WHERE `thread`.`visible`
  AND NOT `thread`.`deleted`
  AND NOT `thread`.`moderated`
  AND NOT `thread`.`private`
  AND `thread`.`wall`
  AND `thread`.`origin`
  AND t.`otype` = ?
  AND t.`type` = ?
  AND t.`term` != ''
  AND i.`received` > DATE_SUB(NOW(), INTERVAL ? HOUR)
GROUP BY `term`
ORDER BY `score` DESC
LIMIT 20", Term::OBJECT_TYPE_POST, Term::HASHTAG, $period);

			if (DBA::isResult($tags)) {
				$tags = DBA::toArray($tagsStmt);
				Cache::set('local_trending_tags', $tags, Cache::HOUR);
			}
		}

		return $tags ?: [];
	}
}
