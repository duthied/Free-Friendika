<?php
/**
 * @file src/Model/PushSubscriber.php
 */
namespace Friendica\Model;

use Friendica\Core\Worker;
use dba;

require_once 'include/dba.php';

class PushSubscriber
{
	/**
	 * @param string $priority Priority for push workers
	 */
	public static function publishFeed($priority = PRIORITY_HIGH)
	{
		// We'll push to each subscriber that has push > 0,
		// i.e. there has been an update (set in notifier.php).
		$subscribers = dba::select('push_subscriber', ['id', 'push', 'callback_url'], ["`push` > 0 AND `next_try` < UTC_TIMESTAMP()"]);

		while ($subscriber = dba::fetch($subscribers)) {
			// We always handle retries with low priority
			if ($subscriber["push"] > 1) {
				$priority = PRIORITY_LOW;
			}
			logger("Publish feed to " . $subscriber["callback_url"] . " with priority " . $priority, LOGGER_DEBUG);
			Worker::add($priority, 'PubSubPublish', (int)$subscriber["id"]);
		}

		dba::close($subscribers);
	}
}
