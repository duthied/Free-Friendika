<?php
/**
 * @file mod/tos.php
 */
use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Module\Login;
use Friendica\Content\Text\BBCode;

function tos_init(App $a) {

	$ret = [];
	Addon::callHooks('tos_init',$ret);

	if (strlen(Config::get('system','singleuser'))) {
		goaway(System::baseUrl()."/profile/" . Config::get('system','singleuser'));
	}

}

function tos_content(App $a) {
	$tpl = get_markup_template('tos.tpl');
	if (Config::get('system', 'tosdisplay'))
	{
	return replace_macros($tpl, [
		'$title' => L10n::t('Terms of Service'),
		'$tostext' => BBCode::convert(Config::get('system', 'tostext')),
		'$displayprivstatement' => Config::get('system', 'tosprivstatement'),
		'$privstatementtitle' => L10n::t('Privacy Statement'),
		'$privoperate' => L10n::t('At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), a nickname and a working email address. The names will be accessible on the profile page of the account by any visitor of the page even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the nodes user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'),
		'$privdelete' => L10n::t('At any point in time a logged in user can export their account data from the <a href="%1$s/settings/uexport">account settings</a>. If the user wants to delete their account they can do so at <a href="%1$s">%1$s</a>. The deletion of the account will be permanent.', System::baseurl().'/removeme')
	]);
	} else {
		return;
	}

	return $o;

}
