<?php

/*
 * Please run composer install and configure htconfig.php
 */

if (PHP_SAPI !== 'cli') {
	die("ERROR: Code execution only possible from CLI\n");
}

if (file_exists('.htconfig.php') && filesize('.htconfig.php')) {
	die("ERROR: Installation was already executed, .htconfig.php already exists\n");
}

// Remove die from config file
$fileContent = file_get_contents('./htconfig.php');
$fileContent = str_replace('die', '//die', $fileContent);
file_put_contents('.htautoinstall.php', $fileContent);

require_once 'boot.php';
require_once 'mod/install.php';
require_once 'include/dba.php';

/*
 * ######################
 * # Initialise the app #
 * ######################
 */
echo "Initializing setup...\n";

$a = new \Friendica\App(__DIR__);
\Friendica\BaseObject::setApp($a);
// add config
require_once '.htautoinstall.php';

echo " Complete!\n\n";

/*
 * #####################
 * # Check basic setup #
 * #####################
 */
echo "Checking basic setup...\n";

$allChecksRequired = in_array('--all-required', $argv, true);

$checkResults = [];
$errorMessage = '';

$checkResults['basic'] = run_basic_checks($a);

foreach ($checkResults['basic'] as $result) {
	if (($allChecksRequired || $result['required'] === true) && $result['status'] === false) {
		$errorMessage .= "--------\n";
		$errorMessage .= $result['title'] . ': ' . $result['help'] . "\n";
	}
}

if ($errorMessage !== '') {
	die($errorMessage);
}

echo " Complete!\n\n";

/*
 * #############################
 * # Check database connection #
 * #############################
 */
echo "Checking database...\n";

$checkResults['db'] = array();
$checkResults['db'][] = run_database_check();

foreach ($checkResults['basic'] as $result) {
	if (($allChecksRequired || $result['required'] === true) && $result['status'] === false) {
		$errorMessage .= "--------\n";
		$errorMessage .= $result['title'] . ': ' . $result['help'] . "\n";
	}
}

if ($errorMessage !== '') {
	die($errorMessage);
}

echo " Complete!\n\n";

/*
 * ####################
 * # Install database #
 * ####################
 */
echo "Inserting data into database...\n";

$checkResults['data'] = load_database();
if ($checkResults['data'] !== '') {
	die("ERROR: DB Database creation error. Is the DB empty?\n");
}
echo " Complete!\n\n";

/*
 * ####################
 * # Copy config file #
 * ####################
 */
echo "Saving config file...\n";
if (!copy('.htautoinstall.php', '.htconfig.php')) {
	die("ERROR: Saving config file failed. Please copy .htautoinstall.php to .htconfig.php manually.\n");
}
echo " Complete!\n\n";

echo "\nInstallation is finished\n";

/**
 * @param App $app
 * @return array
 */
function run_basic_checks($app)
{
	$checks = [];

	check_funcs($checks);
	check_imagik($checks);
	check_htconfig($checks);
	check_smarty3($checks);
	check_keys($checks);

	if (!empty($app->config['php_path'])) {
		check_php($app->config['php_path'], $checks);
	} else {
		die(" ERROR: The php_path is not set in the config. Please check the file .htconfig.php.\n");
	}

	echo " NOTICE: Not checking .htaccess/URL-Rewrite during CLI installation.\n";

	return $checks;
}

function run_database_check()
{
	global $db_host;
	global $db_user;
	global $db_pass;
	global $db_data;

	$result = array(
		'title' => 'MySQL Connection',
		'required' => true,
		'status' => true,
		'help' => '',
	);

	if (!dba::connect($db_host, $db_user, $db_pass, $db_data, true)) {
		$result['status'] = false;
		$result['help'] = 'Failed, please check your MySQL settings and credentials.';
	}

	return $result;
}
