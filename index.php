<?php
/**
 * @file index.php
 * Friendica
 */

use Friendica\App;
use Friendica\Util\LoggerFactory;

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	die('Vendor path not found. Please execute "bin/composer.phar --no-dev install" on the command line in the web root.');
}

require __DIR__ . '/vendor/autoload.php';

$logger = LoggerFactory::create('app');

// We assume that the index.php is called by a frontend process
// The value is set to "true" by default in App
$a = new App(__DIR__, $logger, false);

$a->runFrontend();
