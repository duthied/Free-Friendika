<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\Core\Protocol;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/#lookup
 */
class Lookup extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'acct' => '', // The username or Webfinger address to lookup.
		], $request);

		if (empty($request['acct'])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}
		$contact = Contact::getByURL($request['acct'], null, ['id', 'network', 'failed', 'blocked']);
		if (empty($contact) || ($contact['network'] == Protocol::PHANTOM) || $contact['failed'] || $contact['blocked']) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$this->earlyJsonExit(DI::mstdnAccount()->createFromContactId($contact['id'], $uid));
	}
}
