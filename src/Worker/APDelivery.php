<?php
/**
 * @file src/Worker/APDelivery.php
 */
namespace Friendica\Worker;

use Friendica\BaseObject;
use Friendica\Protocol\ActivityPub;
use Friendica\Model\Item;
use Friendica\Util\HTTPSignature;

class APDelivery extends BaseObject
{
	public static function execute($cmd, $item_id, $inbox)
	{
		logger('Invoked: ' . $cmd . ': ' . $item_id . ' to ' . $inbox, LOGGER_DEBUG);

		if ($cmd == Delivery::MAIL) {
		} elseif ($cmd == Delivery::SUGGESTION) {
		} elseif ($cmd == Delivery::RELOCATION) {
		} else {
			$item = Item::selectFirst(['uid'], ['id' => $item_id]);
			$data = ActivityPub::createActivityFromItem($item_id);
			HTTPSignature::transmit($data, $inbox, $item['uid']);
		}

		return;
	}
}
