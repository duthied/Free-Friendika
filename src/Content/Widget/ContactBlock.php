<?php

/*
 * @file src/Content/Widget/ContactBlock.php
 */

namespace Friendica\Content\Widget;

use Friendica\Content\Text\HTML;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\User;

/**
 * ContactBlock widget
 *
 * @author Hypolite Petovan
 */
class ContactBlock
{
	/**
	 * Get HTML for contact block
	 *
	 * @template widget/contacts.tpl
	 * @hook contact_block_end (contacts=>array, output=>string)
	 * @return string
	 */
	public static function getHTML(array $profile)
	{
		$o = '';

		$shown = PConfig::get($profile['uid'], 'system', 'display_friend_count', 24);
		if ($shown == 0) {
			return $o;
		}

		if (!empty($profile['hide-friends'])) {
			return $o;
		}

		$contacts = [];

		$total = DBA::count('contact', [
			'uid' => $profile['uid'],
			'self' => false,
			'blocked' => false,
			'pending' => false,
			'hidden' => false,
			'archive' => false,
			'network' => [Protocol::DFRN, Protocol::ACTIVITYPUB, Protocol::OSTATUS, Protocol::DIASPORA, Protocol::FEED],
		]);

		$contacts_title = L10n::t('No contacts');

		$micropro = [];

		if ($total) {
			// Only show followed for personal accounts, followers for pages
			if ((($profile['account-type'] ?? '') ?: User::ACCOUNT_TYPE_PERSON) == User::ACCOUNT_TYPE_PERSON) {
				$rel = [Contact::SHARING, Contact::FRIEND];
			} else {
				$rel = [Contact::FOLLOWER, Contact::FRIEND];
			}

			$contact_ids_stmt = DBA::select('contact', ['id'], [
				'uid' => $profile['uid'],
				'self' => false,
				'blocked' => false,
				'pending' => false,
				'hidden' => false,
				'archive' => false,
				'rel' => $rel,
				'network' => Protocol::FEDERATED,
			], ['limit' => $shown]);

			if (DBA::isResult($contact_ids_stmt)) {
				$contact_ids = [];
				while($contact = DBA::fetch($contact_ids_stmt)) {
					$contact_ids[] = $contact["id"];
				}

				$contacts_stmt = DBA::select('contact', ['id', 'uid', 'addr', 'url', 'name', 'thumb', 'network'], ['id' => $contact_ids]);

				if (DBA::isResult($contacts_stmt)) {
					$contacts_title = L10n::tt('%d Contact', '%d Contacts', $total);
					$micropro = [];

					while ($contact = DBA::fetch($contacts_stmt)) {
						$contacts[] = $contact;
						$micropro[] = HTML::micropro($contact, true, 'mpfriend');
					}
				}

				DBA::close($contacts_stmt);
			}

			DBA::close($contact_ids_stmt);
		}

		$tpl = Renderer::getMarkupTemplate('widget/contacts.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$contacts' => $contacts_title,
			'$nickname' => $profile['nickname'],
			'$viewcontacts' => L10n::t('View Contacts'),
			'$micropro' => $micropro,
		]);

		$arr = ['contacts' => $contacts, 'output' => $o];

		Hook::callAll('contact_block_end', $arr);

		return $o;
	}
}
