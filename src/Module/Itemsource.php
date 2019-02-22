<?php

namespace Friendica\Module;

use Friendica\Content\Text\HTML;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Model;
use Friendica\Protocol\ActivityPub\Processor;
use Friendica\Protocol\Diaspora;

/**
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Itemsource extends \Friendica\BaseModule
{
	public static function content()
	{
		if (!is_site_admin()) {
			return;
		}

		$a = self::getApp();

		if (!empty($a->argv[1])) {
			$guid = $a->argv[1];
		}

		$guid = defaults($_REQUEST['guid'], $guid);

		$source = '';
		$item_uri = '';
		$item_id = '';
		$terms = [];
		if (!empty($guid)) {
			$item = Model\Item::selectFirst(['id', 'guid', 'uri'], ['guid' => $guid]);

			$conversation = Model\Conversation::getByItemUri($item['uri']);

			$guid = $item['guid'];
			$item_id = $item['id'];
			$item_uri = $item['uri'];
			$source = $conversation['source'];
			$terms = Model\Term::tagArrayFromItemId($item['id'], [Model\Term::HASHTAG, Model\Term::MENTION, Model\Term::IMPLICIT_MENTION]);
		}

		$tpl = Renderer::getMarkupTemplate('debug/itemsource.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$guid'          => ['guid', L10n::t('Item Guid'), $guid, ''],
			'$source'        => $source,
			'$item_uri'      => $item_uri,
			'$item_id'       => $item_id,
			'$terms'         => $terms,
		]);

		return $o;
	}
}
