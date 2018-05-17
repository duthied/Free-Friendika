<?php
/**
 * @file src/Worker/PubSubPublish.php
 */

namespace Friendica\Worker;

use Friendica\App;
use Friendica\Core\System;
use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Protocol\OStatus;
use Friendica\Util\Network;
use Friendica\Util\DateTimeFormat;
use dba;

require_once 'include/items.php';

class PubSubPublish {
	public static function execute($pubsubpublish_id = 0)
	{
		global $a;

		if ($pubsubpublish_id != 0) {
			self::publish($pubsubpublish_id);
			return;
		}

		// We'll push to each subscriber that has push > 0,
		// i.e. there has been an update (set in notifier.php).
		$subscribers = dba::select('push_subscriber', ['id', 'callback_url'], ["`push` > 0 AND `next_try` < UTC_TIMESTAMP()"]);

		while ($subscriber = dba::fetch($subscribers)) {
			logger("Publish feed to " . $subscriber["callback_url"], LOGGER_DEBUG);
			Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
					'PubSubPublish', (int)$subscriber["id"]);
		}

		dba::close($subscribers);
	}

	private static function publish($id) {
		global $a;

		$subscriber = dba::selectFirst('push_subscriber', [], ['id' => $id]);
		if (!DBM::is_result($subscriber)) {
			return;
		}

		/// @todo Check server status with PortableContact::checkServer()
		// Before this can be done we need a way to safely detect the server url.

		logger("Generate feed of user " . $subscriber['nickname']. " to " . $subscriber['callback_url']. " - last updated " . $subscriber['last_update'], LOGGER_DEBUG);

		$last_update = $subscriber['last_update'];
		$params = OStatus::feed($subscriber['nickname'], $last_update);

		if (!$params) {
			return;
		}

		$hmac_sig = hash_hmac("sha1", $params, $subscriber['secret']);

		$headers = ["Content-type: application/atom+xml",
				sprintf("Link: <%s>;rel=hub,<%s>;rel=self",
					System::baseUrl() . '/pubsubhubbub/' . $subscriber['nickname'],
					$subscriber['topic']),
				"X-Hub-Signature: sha1=" . $hmac_sig];

		logger('POST ' . print_r($headers, true) . "\n" . $params, LOGGER_DATA);

		Network::post($subscriber['callback_url'], $params, $headers);
		$ret = $a->get_curl_code();

		$condition = ['id' => $subscriber['id']];

		if ($ret >= 200 && $ret <= 299) {
			logger('Successfully pushed to ' . $subscriber['callback_url']);

			// set last_update to the "created" date of the last item, and reset push=0
			$fields = ['push' => 0, 'next_try' => NULL_DATE, 'last_update' => $last_update];
			dba::update('push_subscriber', $fields, $condition);

		} else {
			logger('Delivery error when pushing to ' . $subscriber['callback_url'] . ' HTTP: ' . $ret);

			// we use the push variable also as a counter, if we failed we
			// increment this until some upper limit where we give up
			$retrial = $subscriber['push'];

			if ($retrial > 14) {
				dba::update('push_subscriber', ['push' => 0, 'next_try' => NULL_DATE], $condition);
				logger('Delivery error: Giving up for ' . $subscriber['callback_url'], LOGGER_DEBUG);
			} else {
				// Calculate the delay until the next trial
				$delay = (($retrial + 3) ** 4) + (rand(1, 30) * ($retrial + 1));
				$next = DateTimeFormat::utc('now + ' . $delay . ' seconds');

				$retrial = $retrial + 1;

				dba::update('push_subscriber', ['push' => $retrial, 'next_try' => $next], $condition);
				logger('Delivery error: Next try (' . $retrial . ') for ' . $subscriber['callback_url'] . ' at ' . $next, LOGGER_DEBUG);
			}
		}
	}
}
