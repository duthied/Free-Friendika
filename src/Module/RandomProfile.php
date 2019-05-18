<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Model\Contact;
use Friendica\Model\GContact;

/**
 * Redirects to a random profile of this node
 */
class RandomProfile extends BaseModule
{
	public static function content()
	{
		$a = self::getApp();

		$contactUrl = GContact::getRandomUrl();

		if ($contactUrl) {
			$link = Contact::magicLink($contactUrl);
			$a->redirect($link);
		}

		$a->internalRedirect('profile');
	}
}
