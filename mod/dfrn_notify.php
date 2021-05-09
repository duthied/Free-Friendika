<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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
 * The dfrn notify endpoint
 *
 * @see PDF with dfrn specs: https://github.com/friendica/friendica/blob/stable/spec/dfrn2.pdf
 */

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\User;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Diaspora;
use Friendica\Util\Network;
use Friendica\Util\Strings;

function dfrn_notify_post(App $a) {
	$postdata = Network::postdata();

	if (empty($_POST) || !empty($postdata)) {
		$data = json_decode($postdata);
		if (is_object($data)) {
			$nick = $a->argv[1] ?? '';

			$user = DBA::selectFirst('user', [], ['nickname' => $nick, 'account_expired' => false, 'account_removed' => false]);
			if (!DBA::isResult($user)) {
				throw new \Friendica\Network\HTTPException\InternalServerErrorException();
			}
			dfrn_dispatch_private($user, $postdata);
		} elseif (!dfrn_dispatch_public($postdata)) {
			require_once 'mod/salmon.php';
			salmon_post($a, $postdata);
		}
	}

	$dfrn_id      = (!empty($_POST['dfrn_id'])      ? Strings::escapeTags(trim($_POST['dfrn_id']))   : '');
	$dfrn_version = (!empty($_POST['dfrn_version']) ? (float) $_POST['dfrn_version']    : 2.0);
	$challenge    = (!empty($_POST['challenge'])    ? Strings::escapeTags(trim($_POST['challenge'])) : '');
	$data         = $_POST['data'] ?? '';
	$key          = $_POST['key'] ?? '';
	$rino_remote  = (!empty($_POST['rino'])         ? intval($_POST['rino'])            :  0);
	$dissolve     = (!empty($_POST['dissolve'])     ? intval($_POST['dissolve'])        :  0);
	$perm         = (!empty($_POST['perm'])         ? Strings::escapeTags(trim($_POST['perm']))      : 'r');
	$ssl_policy   = (!empty($_POST['ssl_policy'])   ? Strings::escapeTags(trim($_POST['ssl_policy'])): 'none');
	$page         = (!empty($_POST['page'])         ? intval($_POST['page'])            :  0);

	$forum = (($page == 1) ? 1 : 0);
	$prv   = (($page == 2) ? 1 : 0);

	$writable = (-1);
	if ($dfrn_version >= 2.21) {
		$writable = (($perm === 'rw') ? 1 : 0);
	}

	$direction = (-1);
	if (strpos($dfrn_id, ':') == 1) {
		$direction = intval(substr($dfrn_id, 0, 1));
		$dfrn_id = substr($dfrn_id, 2);
	}

	if (!DBA::exists('challenge', ['dfrn-id' => $dfrn_id, 'challenge' => $challenge])) {
		Logger::log('could not match challenge to dfrn_id ' . $dfrn_id . ' challenge=' . $challenge);
		System::xmlExit(3, 'Could not match challenge');
	}

	DBA::delete('challenge', ['dfrn-id' => $dfrn_id, 'challenge' => $challenge]);

	$user = DBA::selectFirst('user', ['uid'], ['nickname' => $a->argv[1]]);
	if (!DBA::isResult($user)) {
		Logger::log('User not found for nickname ' . $a->argv[1]);
		System::xmlExit(3, 'User not found');
	}

	// find the local user who owns this relationship.
	$condition = [];
	switch ($direction) {
		case (-1):
			$condition = ["(`issued-id` = ? OR `dfrn-id` = ?) AND `uid` = ?", $dfrn_id, $dfrn_id, $user['uid']];
			break;
		case 0:
			$condition = ['issued-id' => $dfrn_id, 'duplex' => true, 'uid' => $user['uid']];
			break;
		case 1:
			$condition = ['dfrn-id' => $dfrn_id, 'duplex' => true, 'uid' => $user['uid']];
			break;
		default:
			System::xmlExit(3, 'Invalid direction');
			break; // NOTREACHED
	}

	$contact = DBA::selectFirst('contact', ['id'], $condition);
	if (!DBA::isResult($contact)) {
		Logger::log('contact not found for dfrn_id ' . $dfrn_id);
		System::xmlExit(3, 'Contact not found');
	}

	// $importer in this case contains the contact record for the remote contact joined with the user record of our user.
	$importer = DFRN::getImporter($contact['id'], $user['uid']);

	if ((($writable != (-1)) && ($writable != $importer['writable'])) || ($importer['forum'] != $forum) || ($importer['prv'] != $prv)) {
		$fields = ['writable' => ($writable == (-1)) ? $importer['writable'] : $writable,
			'forum' => $forum, 'prv' => $prv];
		DBA::update('contact', $fields, ['id' => $importer['id']]);

		if ($writable != (-1)) {
			$importer['writable'] = $writable;
		}
		$importer['forum'] = $page;
	}


	// if contact's ssl policy changed, update our links

	$importer = Contact::updateSslPolicy($importer, $ssl_policy);

	Logger::log('data: ' . $data, Logger::DATA);

	if ($dissolve == 1) {
		// Relationship is dissolved permanently
		Contact::remove($importer['id']);
		Logger::log('relationship dissolved : ' . $importer['name'] . ' dissolved ' . $importer['username']);
		System::xmlExit(0, 'relationship dissolved');
	}

	$rino = DI::config()->get('system', 'rino_encrypt');
	$rino = intval($rino);

	if (strlen($key)) {

		// if local rino is lower than remote rino, abort: should not happen!
		// but only for $remote_rino > 1, because old code did't send rino version
		if ($rino_remote > 1 && $rino < $rino_remote) {
			Logger::log("rino version '$rino_remote' is lower than supported '$rino'");
			System::xmlExit(0, "rino version '$rino_remote' is lower than supported '$rino'");
		}

		$rawkey = hex2bin(trim($key));
		Logger::log('rino: md5 raw key: ' . md5($rawkey), Logger::DATA);

		$final_key = '';

		if ($dfrn_version >= 2.1) {
			if (($importer['duplex'] && strlen($importer['cprvkey'])) || !strlen($importer['cpubkey'])) {
				openssl_private_decrypt($rawkey, $final_key, $importer['cprvkey']);
			} else {
				openssl_public_decrypt($rawkey, $final_key, $importer['cpubkey']);
			}
		} else {
			if (($importer['duplex'] && strlen($importer['cpubkey'])) || !strlen($importer['cprvkey'])) {
				openssl_public_decrypt($rawkey, $final_key, $importer['cpubkey']);
			} else {
				openssl_private_decrypt($rawkey, $final_key, $importer['cprvkey']);
			}
		}

		switch ($rino_remote) {
			case 0:
			case 1:
				// we got a key. old code send only the key, without RINO version.
				// we assume RINO 1 if key and no RINO version
				$data = DFRN::aesDecrypt(hex2bin($data), $final_key);
				break;
			default:
				Logger::log("rino: invalid sent version '$rino_remote'");
				System::xmlExit(0, "Invalid sent version '$rino_remote'");
		}

		Logger::log('rino: decrypted data: ' . $data, Logger::DATA);
	}

	Logger::log('Importing post from ' . $importer['addr'] . ' to ' . $importer['nickname'] . ' with the RINO ' . $rino_remote . ' encryption.', Logger::DEBUG);

	$ret = DFRN::import($data, $importer, Conversation::PARCEL_LEGACY_DFRN, Conversation::PUSH);
	System::xmlExit($ret, 'Processed');

	// NOTREACHED
}

function dfrn_dispatch_public($postdata)
{
	$msg = Diaspora::decodeRaw($postdata, '', true);
	if (!$msg) {
		// We have to fail silently to be able to hand it over to the salmon parser
		return false;
	}

	// Fetch the corresponding public contact
	$contact_id = Contact::getIdForURL($msg['author']);
	if (empty($contact_id)) {
		Logger::log('Contact not found for address ' . $msg['author']);
		System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
	}

	$importer = DFRN::getImporter($contact_id);

	// This should never fail
	if (empty($importer)) {
		Logger::log('Contact not found for address ' . $msg['author']);
		System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
	}

	Logger::log('Importing post from ' . $msg['author'] . ' with the public envelope.', Logger::DEBUG);

	// Now we should be able to import it
	$ret = DFRN::import($msg['message'], $importer, Conversation::PARCEL_DIASPORA_DFRN, Conversation::RELAY);
	System::xmlExit($ret, 'Done');
}

function dfrn_dispatch_private($user, $postdata)
{
	$msg = Diaspora::decodeRaw($postdata, $user['prvkey'] ?? '');
	if (!$msg) {
		System::xmlExit(4, 'Unable to parse message');
	}

	// Check if the user has got this contact
	$cid = Contact::getIdForURL($msg['author'], $user['uid']);
	if (!$cid) {
		// Otherwise there should be a public contact
		$cid = Contact::getIdForURL($msg['author']);
		if (!$cid) {
			Logger::log('Contact not found for address ' . $msg['author']);
			System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
		}
	}

	$importer = DFRN::getImporter($cid, $user['uid']);

	// This should never fail
	if (empty($importer)) {
		Logger::log('Contact not found for address ' . $msg['author']);
		System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
	}

	Logger::log('Importing post from ' . $msg['author'] . ' to ' . $user['nickname'] . ' with the private envelope.', Logger::DEBUG);

	// Now we should be able to import it
	$ret = DFRN::import($msg['message'], $importer, Conversation::PARCEL_DIASPORA_DFRN, Conversation::PUSH);
	System::xmlExit($ret, 'Done');
}

function dfrn_notify_content(App $a) {

	if (!empty($_GET['dfrn_id'])) {

		/*
		 * initial communication from external contact, $direction is their direction.
		 * If this is a duplex communication, ours will be the opposite.
		 */

		$dfrn_id = Strings::escapeTags(trim($_GET['dfrn_id']));
		$rino_remote = (!empty($_GET['rino']) ? intval($_GET['rino']) : 0);
		$type = "";
		$last_update = "";

		Logger::log('new notification dfrn_id=' . $dfrn_id);

		$direction = (-1);
		if (strpos($dfrn_id,':') == 1) {
			$direction = intval(substr($dfrn_id,0,1));
			$dfrn_id = substr($dfrn_id,2);
		}

		$hash = Strings::getRandomHex();

		$status = 0;

		DBA::delete('challenge', ["`expire` < ?", time()]);

		$fields = ['challenge' => $hash, 'dfrn-id' => $dfrn_id, 'expire' => time() + 90,
			'type' => $type, 'last_update' => $last_update];
		DBA::insert('challenge', $fields);

		Logger::log('challenge=' . $hash, Logger::DATA);

		$user = DBA::selectFirst('user', ['uid'], ['nickname' => $a->argv[1]]);
		if (!DBA::isResult($user)) {
			Logger::log('User not found for nickname ' . $a->argv[1]);
			exit();
		}

		$condition = [];
		switch ($direction) {
			case (-1):
				$condition = ["(`issued-id` = ? OR `dfrn-id` = ?) AND `uid` = ?", $dfrn_id, $dfrn_id, $user['uid']];
				$my_id = $dfrn_id;
				break;
			case 0:
				$condition = ['issued-id' => $dfrn_id, 'duplex' => true, 'uid' => $user['uid']];
				$my_id = '1:' . $dfrn_id;
				break;
			case 1:
				$condition = ['dfrn-id' => $dfrn_id, 'duplex' => true, 'uid' => $user['uid']];
				$my_id = '0:' . $dfrn_id;
				break;
			default:
				$status = 1;
				$my_id = '';
				break;
		}

		$contact = DBA::selectFirst('contact', ['id'], $condition);
		if (!DBA::isResult($contact)) {
			Logger::log('contact not found for dfrn_id ' . $dfrn_id);
			System::xmlExit(3, 'Contact not found');
		}

		// $importer in this case contains the contact record for the remote contact joined with the user record of our user.
		$importer = DFRN::getImporter($contact['id'], $user['uid']);
		if (empty($importer)) {
			Logger::log('No importer data found for user ' . $a->argv[1] . ' and contact ' . $dfrn_id);
			exit();
		}

		Logger::log("Remote rino version: ".$rino_remote." for ".$importer["url"], Logger::DATA);

		$challenge    = '';
		$encrypted_id = '';
		$id_str       = $my_id . '.' . mt_rand(1000,9999);

		$prv_key = trim($importer['cprvkey']);
		$pub_key = trim($importer['cpubkey']);
		$dplx    = intval($importer['duplex']);

		if (($dplx && strlen($prv_key)) || (strlen($prv_key) && !strlen($pub_key))) {
			openssl_private_encrypt($hash, $challenge, $prv_key);
			openssl_private_encrypt($id_str, $encrypted_id, $prv_key);
		} elseif (strlen($pub_key)) {
			openssl_public_encrypt($hash, $challenge, $pub_key);
			openssl_public_encrypt($id_str, $encrypted_id, $pub_key);
		} else {
			/// @TODO these kind of else-blocks are making the code harder to understand
			$status = 1;
		}

		$challenge    = bin2hex($challenge);
		$encrypted_id = bin2hex($encrypted_id);


		$rino = DI::config()->get('system', 'rino_encrypt');
		$rino = intval($rino);

		Logger::log("Local rino version: ". $rino, Logger::DATA);

		// if requested rino is lower than enabled local rino, lower local rino version
		// if requested rino is higher than enabled local rino, reply with local rino
		if ($rino_remote < $rino) {
			$rino = $rino_remote;
		}

		if (($importer['rel'] && ($importer['rel'] != Contact::SHARING)) || ($importer['page-flags'] == User::PAGE_FLAGS_COMMUNITY)) {
			$perm = 'rw';
		} else {
			$perm = 'r';
		}

		header("Content-type: text/xml");

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n"
			. '<dfrn_notify>' . "\r\n"
			. "\t" . '<status>' . $status . '</status>' . "\r\n"
			. "\t" . '<dfrn_version>' . DFRN_PROTOCOL_VERSION . '</dfrn_version>' . "\r\n"
			. "\t" . '<rino>' . $rino . '</rino>' . "\r\n"
			. "\t" . '<perm>' . $perm . '</perm>' . "\r\n"
			. "\t" . '<dfrn_id>' . $encrypted_id . '</dfrn_id>' . "\r\n"
			. "\t" . '<challenge>' . $challenge . '</challenge>' . "\r\n"
			. '</dfrn_notify>' . "\r\n";

		exit();
	}
}
