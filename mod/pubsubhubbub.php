<?php

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;

function post_var($name) {
	return (x($_POST, $name)) ? notags(trim($_POST[$name])) : '';
}

function pubsubhubbub_init(App $a) {
	// PuSH subscription must be considered "public" so just block it
	// if public access isn't enabled.
	if (Config::get('system', 'block_public')) {
		System::httpExit(403);
	}

	// Subscription request from subscriber
	// https://pubsubhubbub.googlecode.com/git/pubsubhubbub-core-0.4.html#anchor4
	// Example from GNU Social:
	// [hub_mode] => subscribe
	// [hub_callback] => http://status.local/main/push/callback/1
	// [hub_verify] => sync
	// [hub_verify_token] => af11...
	// [hub_secret] => af11...
	// [hub_topic] => http://friendica.local/dfrn_poll/sazius

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$hub_mode = post_var('hub_mode');
		$hub_callback = post_var('hub_callback');
		$hub_verify = post_var('hub_verify');
		$hub_verify_token = post_var('hub_verify_token');
		$hub_secret = post_var('hub_secret');
		$hub_topic = post_var('hub_topic');

		// check for valid hub_mode
		if ($hub_mode === 'subscribe') {
			$subscribe = 1;
		} elseif ($hub_mode === 'unsubscribe') {
			$subscribe = 0;
		} else {
			logger("Invalid hub_mode=$hub_mode, ignoring.");
			System::httpExit(404);
		}

		logger("$hub_mode request from " . $_SERVER['REMOTE_ADDR']);

		// get the nick name from the topic, a bit hacky but needed as a fallback
		$nick = substr(strrchr($hub_topic, "/"), 1);

		// Normally the url should now contain the nick name as last part of the url
		if ($a->argc > 1) {
			$nick = $a->argv[1];
		}

		if (!$nick) {
			logger('Bad hub_topic=$hub_topic, ignoring.');
			System::httpExit(404);
		}

		// fetch user from database given the nickname
		$condition = ['nickname' => $nick, 'account_expired' => false, 'account_removed' => false];
		$owner = dba::selectFirst('user', ['uid', 'hidewall'], $condition);
		if (!DBM::is_result($owner)) {
			logger('Local account not found: ' . $nick . ' - topic: ' . $hub_topic . ' - callback: ' . $hub_callback);
			System::httpExit(404);
		}

		// abort if user's wall is supposed to be private
		if ($owner['hidewall']) {
			logger('Local user ' . $nick . 'has chosen to hide wall, ignoring.');
			System::httpExit(403);
		}

		// get corresponding row from contact table
		$condition = ['uid' => $owner['uid'], 'blocked' => false,
			'pending' => false, 'self' => true];
		$contact = dba::selectFirst('contact', ['poll'], $condition);
		if (!DBM::is_result($contact)) {
			logger('Self contact for user ' . $owner['uid'] . ' not found.');
			System::httpExit(404);
		}

		// sanity check that topic URLs are the same
		$hub_topic2 = str_replace('/feed/', '/dfrn_poll/', $hub_topic);
		if (!link_compare($hub_topic, $contact['poll']) && !link_compare($hub_topic2, $contact['poll'])) {
			logger('Hub topic ' . $hub_topic . ' != ' . $contact['poll']);
			System::httpExit(404);
		}

		// do subscriber verification according to the PuSH protocol
		$hub_challenge = random_string(40);
		$params = 'hub.mode=' .
			($subscribe == 1 ? 'subscribe' : 'unsubscribe') .
			'&hub.topic=' . urlencode($hub_topic) .
			'&hub.challenge=' . $hub_challenge .
			'&hub.lease_seconds=604800' .
			'&hub.verify_token=' . $hub_verify_token;

		// lease time is hard coded to one week (in seconds)
		// we don't actually enforce the lease time because GNU
		// Social/StatusNet doesn't honour it (yet)

		$body = Network::fetchUrl($hub_callback . "?" . $params);
		$ret = $a->get_curl_code();

		// give up if the HTTP return code wasn't a success (2xx)
		if ($ret < 200 || $ret > 299) {
			logger("Subscriber verification for $hub_topic at $hub_callback returned $ret, ignoring.");
			System::httpExit(404);
		}

		// check that the correct hub_challenge code was echoed back
		if (trim($body) !== $hub_challenge) {
			logger("Subscriber did not echo back hub.challenge, ignoring.");
			logger("\"$hub_challenge\" != \"".trim($body)."\"");
			System::httpExit(404);
		}

		// fetch the old subscription if it exists
		$subscriber = dba::selectFirst('push_subscriber', ['last_update', 'push'], ['callback_url' => $hub_callback]);

		// delete old subscription if it exists
		dba::delete('push_subscriber', ['callback_url' => $hub_callback]);

		if ($subscribe) {
			$last_update = DateTimeFormat::utcNow();
			$push_flag = 0;

			// if we are just updating an old subscription, keep the
			// old values for last_update but reset the push
			if (DBM::is_result($subscriber)) {
				$last_update = $subscriber['last_update'];
				$push_flag = min($subscriber['push'], 1);
			}

			// subscribe means adding the row to the table
			$fields = ['uid' => $owner['uid'], 'callback_url' => $hub_callback,
				'topic' => $hub_topic, 'nickname' => $nick, 'push' => $push_flag,
				'last_update' => $last_update, 'renewed' => DateTimeFormat::utcNow(),
				'secret' => $hub_secret];
			dba::insert('push_subscriber', $fields);

			logger("Successfully subscribed [$hub_callback].");
		} else {
			logger("Successfully unsubscribed [$hub_callback].");
			// we do nothing here, since the row was already deleted
		}
		System::httpExit(202);
	}
	killme();
}
