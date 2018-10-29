<?php

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\Core;
use Friendica\Core\L10n;
use Friendica\Util\Temporal;

class Install extends BaseModule
{
	/**
	 * Step one - System check
	 */
	const SYSTEM_CHECK = 1;
	/**
	 * Step two - Database configuration
	 */
	const DATABASE_CONFIG = 2;
	/**
	 * Step three - Adapat site settings
	 */
	const SITE_SETTINGS = 3;
	/**
	 * Step four - All steps finished
	 */
	const FINISHED = 4;

	// Default values for the install page
	const DEFAULT_LANG = 'en';
	const DEFAULT_TZ   = 'America/Los_Angeles';
	const DEFAULT_HOST = 'localhost';

	/**
	 * @var int The current step of the wizard
	 */
	private static $currentWizardStep;

	public static function init()
	{
		$a = self::getApp();

		// route: install/testrwrite
		// $baseurl/install/testrwrite to test if rewrite in .htaccess is working
		if ($a->getArgumentValue(1, '') == 'testrewrite') {
			// Status Code 204 means that it worked without content
			Core\System::httpExit(204);
		}

		// We overwrite current theme css, because during install we may not have a working mod_rewrite
		// so we may not have a css at all. Here we set a static css file for the install procedure pages
		$a->setConfigValue('system', 'value', '../install');
		$a->theme['stylesheet'] = $a->getBaseURL() . '/view/install/style.css';

		self::$currentWizardStep = defaults($_POST, 'pass', self::SYSTEM_CHECK);
	}

	public static function post()
	{
		$a = self::getApp();

		switch (self::$currentWizardStep) {
			case self::SYSTEM_CHECK:
			case self::DATABASE_CONFIG:
				// Nothing to do in these steps
				return;

			case self::SITE_SETTINGS:
				$dbhost  = notags(trim(defaults($_POST, 'dbhost', self::DEFAULT_HOST)));
				$dbuser  = notags(trim(defaults($_POST, 'dbuser', '')));
				$dbpass  = notags(trim(defaults($_POST, 'dbpass', '')));
				$dbdata  = notags(trim(defaults($_POST, 'dbdata', '')));

				require_once 'include/dba.php';
				if (!DBA::connect($dbhost, $dbuser, $dbpass, $dbdata)) {
					$a->data['db_conn_failed'] = true;
				}

				return;

			case self::FINISHED:
				$urlpath   = $a->getURLPath();
				$dbhost    = notags(trim(defaults($_POST, 'dbhost', self::DEFAULT_HOST)));
				$dbuser    = notags(trim(defaults($_POST, 'dbuser', '')));
				$dbpass    = notags(trim(defaults($_POST, 'dbpass', '')));
				$dbdata    = notags(trim(defaults($_POST, 'dbdata', '')));
				$phpath    = notags(trim(defaults($_POST, 'phpath', '')));
				$timezone  = notags(trim(defaults($_POST, 'timezone', self::DEFAULT_TZ)));
				$language  = notags(trim(defaults($_POST, 'language', self::DEFAULT_LANG)));
				$adminmail = notags(trim(defaults($_POST, 'adminmail', '')));

				// connect to db
				DBA::connect($dbhost, $dbuser, $dbpass, $dbdata);

				$install = new Core\Install();

				$errors = $install->createConfig($phpath, $urlpath, $dbhost, $dbuser, $dbpass, $dbdata, $timezone, $language, $adminmail, $a->getBasePath());

				if ($errors !== true) {
					$a->data['txt'] = $errors;
					return;
				}

				$errors = DBStructure::update(false, true, true);

				if ($errors) {
					$a->data['db_failed'] = $errors;
				} else {
					$a->data['db_installed'] = true;
				}

				return;

			default:
				return;
		}
	}

	public static function content()
	{
		$a = self::getApp();

		$output = '';

		$install_title = L10n::t('Friendica Communctions Server - Setup');
		$wizard_status = self::checkWizardStatus($a);

		switch (self::$currentWizardStep) {
			case self::SYSTEM_CHECK:
				$phppath = defaults($_POST, 'phpath', null);

				$install = new Core\Install();
				$status = $install->checkAll($a->getBaseURL(), $phppath);

				$tpl = get_markup_template('install_checks.tpl');
				$output .= replace_macros($tpl, [
					'$title'		=> $install_title,
					'$pass'			=> L10n::t('System check'),
					'$checks'		=> $install->getChecks(),
					'$passed'		=> $status,
					'$see_install'	=> L10n::t('Please see the file "Install.txt".'),
					'$next' 		=> L10n::t('Next'),
					'$reload' 		=> L10n::t('Check again'),
					'$phpath' 		=> $phppath,
					'$baseurl' 		=> $a->getBaseURL()
				]);
				break;

			case self::DATABASE_CONFIG:
				$dbhost    = notags(trim(defaults($_POST, 'dbhost'   , self::DEFAULT_HOST)));
				$dbuser    = notags(trim(defaults($_POST, 'dbuser'   , '')));
				$dbpass    = notags(trim(defaults($_POST, 'dbpass'   , '')));
				$dbdata    = notags(trim(defaults($_POST, 'dbdata'   , '')));
				$phpath    = notags(trim(defaults($_POST, 'phpath'   , '')));
				$adminmail = notags(trim(defaults($_POST, 'adminmail', '')));

				$tpl = get_markup_template('install_db.tpl');
				$output .= replace_macros($tpl, [
					'$title' 	=> $install_title,
					'$pass' 	=> L10n::t('Database connection'),
					'$info_01' 	=> L10n::t('In order to install Friendica we need to know how to connect to your database.'),
					'$info_02' 	=> L10n::t('Please contact your hosting provider or site administrator if you have questions about these settings.'),
					'$info_03' 	=> L10n::t('The database you specify below should already exist. If it does not, please create it before continuing.'),
					'$status' 	=> $wizard_status,
					'$dbhost' 	=> ['dbhost',
									L10n::t('Database Server Name'),
									$dbhost,
									'',
									'required'],
					'$dbuser' 	=> ['dbuser',
									L10n::t('Database Login Name'),
									$dbuser,
									'',
									'required',
									'autofocus'],
					'$dbpass' 	=> ['dbpass',
									L10n::t('Database Login Password'),
									$dbpass,
									L10n::t("For security reasons the password must not be empty"),
									'required'],
					'$dbdata' 	=> ['dbdata',
									L10n::t('Database Name'),
									$dbdata,
									'',
									'required'],
					'$adminmail' => ['adminmail',
									L10n::t('Site administrator email address'),
									$adminmail,
									L10n::t('Your account email address must match this in order to use the web admin panel.'),
									'required',
									'autofocus',
									'email'],
					'$lbl_10' 	=> L10n::t('Please select a default timezone for your website'),
					'$baseurl' 	=> $a->getBaseURL(),
					'$phpath' 	=> $phpath,
					'$submit' 	=> L10n::t('Submit')
				]);
				break;
			case self::SITE_SETTINGS:
				$dbhost = notags(trim(defaults($_POST, 'dbhost', self::DEFAULT_HOST)));
				$dbuser = notags(trim(defaults($_POST, 'dbuser', ''                )));
				$dbpass = notags(trim(defaults($_POST, 'dbpass', ''                )));
				$dbdata = notags(trim(defaults($_POST, 'dbdata', ''                )));
				$phpath = notags(trim(defaults($_POST, 'phpath', ''                )));

				$adminmail = notags(trim(defaults($_POST, 'adminmail', '')));

				$timezone = defaults($_POST, 'timezone', self::DEFAULT_TZ);
				/* Installed langs */
				$lang_choices = L10n::getAvailableLanguages();

				$tpl = get_markup_template('install_settings.tpl');
				$output .= replace_macros($tpl, [
					'$title' 		=> $install_title,
					'$pass' 		=> L10n::t('Site settings'),
					'$status' 		=> $wizard_status,
					'$dbhost' 		=> $dbhost,
					'$dbuser' 		=> $dbuser,
					'$dbpass' 		=> $dbpass,
					'$dbdata' 		=> $dbdata,
					'$phpath' 		=> $phpath,
					'$adminmail'	=> ['adminmail', L10n::t('Site administrator email address'), $adminmail, L10n::t('Your account email address must match this in order to use the web admin panel.'), 'required', 'autofocus', 'email'],
					'$timezone' 	=> Temporal::getTimezoneField('timezone', L10n::t('Please select a default timezone for your website'), $timezone, ''),
					'$language' 	=> ['language',
										L10n::t('System Language:'), #
										self::DEFAULT_LANG,
										L10n::t('Set the default language for your Friendica installation interface and to send emails.'),
										$lang_choices],
					'$baseurl' 		=> $a->getBaseURL(),
					'$submit' 		=> L10n::t('Submit')
				]);
				break;

			case self::FINISHED:
				$db_return_text = "";

				if (defaults($a->data, 'db_installed', false)) {
					$txt = '<p style="font-size: 130%;">';
					$txt .= L10n::t('Your Friendica site database has been installed.') . EOL;
					$db_return_text .= $txt;
				}

				if (defaults($a->data, 'db_failed', false)) {
					$txt = L10n::t('You may need to import the file "database.sql" manually using phpmyadmin or mysql.') . EOL;
					$txt .= L10n::t('Please see the file "INSTALL.txt".') . EOL ."<hr>";
					$txt .= "<pre>".$a->data['db_failed'] . "</pre>". EOL;
					$db_return_text .= $txt;
				}

				if (isset($a->data['txt']) && strlen($a->data['txt'])) {
					$db_return_text .= self::manualConfig($a);
				}

				$tpl = get_markup_template('install.tpl');
				$output .= replace_macros($tpl, [
					'$title' => $install_title,
					'$pass' => "",
					'$text' => $db_return_text . self::whatNext($a),
				]);

				break;
		}

		return $output;
	}

	/**
	 * @param App $a The global Friendica App
	 *
	 * @return string The status of Wizard steps
	 */
	private static function checkWizardStatus($a)
	{
		$wizardStatus = "";

		if (defaults($a->data, 'db_conn_failed', false)) {
			self::$currentWizardStep = 2;
			$wizardStatus = L10n::t('Could not connect to database.');
		}

		if (defaults($a->data, 'db_create_failed', false)) {
			self::$currentWizardStep = 2;
			$wizardStatus = L10n::t('Could not create table.');
		}

		if (DBA::connected()) {
			if (DBA::count('user')) {
				self::$currentWizardStep = 2;
				$wizardStatus = L10n::t('Database already in use.');
			}
		}

		return $wizardStatus;
	}

	/**
	 * Creates the text for manual config
	 *
	 * @param App $a The global App
	 *
	 * @return string The manual config text
	 */
	private static function manualConfig($a)
	{
		$data = htmlentities($a->data['txt'], ENT_COMPAT,  'UTF-8');
		$output = L10n::t('The database configuration file "config/local.ini.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.');
		$output .= "<textarea rows=\"24\" cols=\"80\" >$data</textarea>";
		return $output;
	}

	/**
	 * Creates the text for the next steps
	 *
	 * @param App $a The global App
	 *
	 * @return string The text for the next steps
	 */
	private static function whatNext($a)
	{
		$baseurl = $a->getBaseUrl();
		return
			L10n::t('<h1>What next</h1>')
			. "<p>".L10n::t('IMPORTANT: You will need to [manually] setup a scheduled task for the worker.')
			. L10n::t('Please see the file "INSTALL.txt".')
			. "</p><p>"
			. L10n::t('Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.', $baseurl)
			. "</p>";
	}
}
