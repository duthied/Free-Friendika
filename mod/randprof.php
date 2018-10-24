<?php
/**
 * @file mod/randprof.php
 */
use Friendica\App;
use Friendica\Core\System;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Profile;

function randprof_init(App $a)
{
	$x = GContact::getRandomUrl();

	if ($x) {
		$link = Contact::magicLink($x);
		// @TODO making the return of magicLink save to use either externalRedirect or internalRedirect
		$a->redirect($link);
	}

	$a->internalRedirect('profile');
}
