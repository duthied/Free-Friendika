<?php
/**
 * @file mod/admin.php
 *
 * @brief Friendica admin
 */

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Content\Pager;
use Friendica\Content\Text\Markdown;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\StorageManager;
use Friendica\Core\System;
use Friendica\Core\Theme;
use Friendica\Core\Update;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Register;
use Friendica\Model\User;
use Friendica\Module;
use Friendica\Module\Login;
use Friendica\Module\Tos;
use Friendica\Protocol\PortableContact;
use Friendica\Util\Arrays;
use Friendica\Util\BasePath;
use Friendica\Util\BaseURL;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use Psr\Log\LogLevel;

/**
 * @brief Process send data from the admin panels subpages
 *
 * This function acts as relay for processing the data send from the subpages
 * of the admin panel. Depending on the 1st parameter of the url (argv[1])
 * specialized functions are called to process the data from the subpages.
 *
 * The function itself does not return anything, but the subsequently function
 * return the HTML for the pages of the admin panel.
 *
 * @param App $a
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function admin_post(App $a)
{
	if (!is_site_admin()) {
		return;
	}

	// do not allow a page manager to access the admin panel at all.

	if (!empty($_SESSION['submanage'])) {
		return;
	}

	$return_path = 'admin';
	if ($a->argc > 1) {
		switch ($a->argv[1]) {
			case 'deleteitem':
				admin_page_deleteitem_post($a);
				break;
		}
	}

	$a->internalRedirect($return_path);
	return; // NOTREACHED
}

/**
 * @brief Generates content of the admin panel pages
 *
 * This function generates the content for the admin panel. It consists of the
 * aside menu (same for the entire admin panel) and the code for the soecified
 * subpage of the panel.
 *
 * The structure of the adress is: /admin/subpage/details though "details" is
 * only necessary for some subpages, like themes or addons where it is the name
 * of one theme resp. addon from which the details should be shown. Content for
 * the subpages is generated in separate functions for each of the subpages.
 *
 * The returned string hold the generated HTML code of the page.
 *
 * @param App $a
 * @return string
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function admin_content(App $a)
{
	if (!is_site_admin()) {
		return Login::form();
	}

	if (!empty($_SESSION['submanage'])) {
		return "";
	}

	// APC deactivated, since there are problems with PHP 5.5
	//if (function_exists("apc_delete")) {
	// $toDelete = new APCIterator('user', APC_ITER_VALUE);
	// apc_delete($toDelete);
	//}
	// Header stuff
	$a->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('admin/settings_head.tpl'), []);

	/*
	 * Side bar links
	 */
	$aside_tools = [];
	// array(url, name, extra css classes)
	// not part of $aside to make the template more adjustable
	$aside_sub = [
		'information' => [L10n::t('Information'), [
			'overview' => ['admin/', L10n::t('Overview'), 'overview'],
			'federation'   => ['admin/federation/'  , L10n::t('Federation Statistics'), 'federation']]],
		'configuration' => [L10n::t('Configuration'), [
			'site'         => ['admin/site/'        , L10n::t('Site')                    , 'site'],
			'users'        => ['admin/users/'       , L10n::t('Users')                   , 'users'],
			'addons'       => ['admin/addons/'      , L10n::t('Addons')                  , 'addons'],
			'themes'       => ['admin/themes/'      , L10n::t('Themes')                  , 'themes'],
			'features'     => ['admin/features/'    , L10n::t('Additional features')     , 'features'],
			'tos'          => ['admin/tos/'         , L10n::t('Terms of Service')        , 'tos']]],
		'database' => [L10n::t('Database'), [
			'dbsync'       => ['admin/dbsync/'      , L10n::t('DB updates')              , 'dbsync'],
			'deferred'     => ['admin/deferred/'    , L10n::t('Inspect Deferred Workers'), 'deferred'],
			'workerqueue'  => ['admin/workerqueue/' , L10n::t('Inspect worker Queue')    , 'workerqueue']]],
		'tools' => [L10n::t('Tools'), [
			'contactblock' => ['admin/contactblock/', L10n::t('Contact Blocklist')       , 'contactblock'],
			'blocklist'    => ['admin/blocklist/'   , L10n::t('Server Blocklist')        , 'blocklist'],
			'deleteitem'   => ['admin/deleteitem/'  , L10n::t('Delete Item')             , 'deleteitem'],]],
		'logs' => [L10n::t('Logs'), [
			'logsconfig' => ['admin/logs/', L10n::t('Logs'), 'logs'],
			'logsview' => ['admin/viewlogs/', L10n::t('View Logs'), 'viewlogs']
		]],
		'diagnostics' => [L10n::t('Diagnostics'), [
			'phpinfo' => ['phpinfo/', L10n::t('PHP Info'), 'phpinfo'],
			'probe' => ['probe/', L10n::t('probe address'), 'probe'],
			'webfinger' =>['webfinger/', L10n::t('check webfinger'), 'webfinger']
		]]
	];

	$aside_tools['addons_admin'] = [];

	$t = Renderer::getMarkupTemplate('admin/aside.tpl');
	$a->page['aside'] .= Renderer::replaceMacros($t, [
		'$admin' => $aside_tools,
		'$subpages' => $aside_sub,
		'$admtxt' => L10n::t('Admin'),
		'$plugadmtxt' => L10n::t('Addon Features'),
		'$h_pending' => L10n::t('User registrations waiting for confirmation'),
		'$admurl' => "admin/"
	]);

	// Page content
	$o = '';
	// urls
	if ($a->argc > 1) {
		switch ($a->argv[1]) {
			case 'deleteitem':
				$o = admin_page_deleteitem($a);
				break;
			default:
				notice(L10n::t("Item not found."));
		}
	}

	if ($a->isAjax()) {
		echo $o;
		exit();
	} else {
		return $o;
	}
}

/**
 * @brief Subpage where the admin can delete an item from their node given the GUID
 *
 * This subpage of the admin panel offers the nodes admin to delete an item from
 * the node, given the GUID or the display URL such as http://example.com/display/123456.
 * The item will then be marked as deleted in the database and processed accordingly.
 *
 * @param App $a
 * @return string
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function admin_page_deleteitem(App $a)
{
	$t = Renderer::getMarkupTemplate('admin/deleteitem.tpl');

	return Renderer::replaceMacros($t, [
		'$title' => L10n::t('Administration'),
		'$page' => L10n::t('Delete Item'),
		'$submit' => L10n::t('Delete this Item'),
		'$intro1' => L10n::t('On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'),
		'$intro2' => L10n::t('You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'),
		'$deleteitemguid' => ['deleteitemguid', L10n::t("GUID"), '', L10n::t("The GUID of the item you want to delete."), 'required', 'autofocus'],
		'$baseurl' => System::baseUrl(),
		'$form_security_token' => BaseModule::getFormSecurityToken("admin_deleteitem")
	]);
}

/**
 * @brief Process send data from Admin Delete Item Page
 *
 * The GUID passed through the form should be only the GUID. But we also parse
 * URLs like the full /display URL to make the process more easy for the admin.
 *
 * @param App $a
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function admin_page_deleteitem_post(App $a)
{
	if (empty($_POST['page_deleteitem_submit'])) {
		return;
	}

	BaseModule::checkFormSecurityTokenRedirectOnError('/admin/deleteitem/', 'admin_deleteitem');

	if (!empty($_POST['page_deleteitem_submit'])) {
		$guid = trim(Strings::escapeTags($_POST['deleteitemguid']));
		// The GUID should not include a "/", so if there is one, we got an URL
		// and the last part of it is most likely the GUID.
		if (strpos($guid, '/')) {
			$guid = substr($guid, strrpos($guid, '/') + 1);
		}
		// Now that we have the GUID, drop those items, which will also delete the
		// associated threads.
		Item::delete(['guid' => $guid]);
	}

	info(L10n::t('Item marked for deletion.') . EOL);
	$a->internalRedirect('admin/deleteitem');
	return; // NOTREACHED
}

function admin_page_server_vital()
{
	// Fetch the host-meta to check if this really is a vital server
	return Network::curl(System::baseUrl() . '/.well-known/host-meta')->isSuccess();
}
