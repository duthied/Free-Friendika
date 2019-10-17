<?php

namespace Friendica\Content\Widget;

use Friendica\Core\Cache;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Model\Term;

/**
 * Trending tags aside widget for the community pages, handles both local and global scopes
 *
 * @package Friendica\Content\Widget
 */
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
			$tags = Term::getLocalTrendingHashtags($period, 20);
		} else {
			$tags = Term::getGlobalTrendingHashtags($period, 20);
		}

		$tpl = Renderer::getMarkupTemplate('widget/trending_tags.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$title' => L10n::tt('Trending Tags (last %d hour)', 'Trending Tags (last %d hours)', $period),
			'$more' => L10n::t('More Trending Tags'),
			'$tags' => $tags,
		]);

		return $o;
	}
}
