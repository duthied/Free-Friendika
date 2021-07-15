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
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Network\HTTPException;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Diaspora;
use Friendica\Util\Network;

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
	throw new HTTPException\BadRequestException();
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
	throw new HTTPException\NotFoundException();
}
