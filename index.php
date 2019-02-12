<?php
/**
 * @file index.php
 * Friendica
 */

use Friendica\Factory;

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	die('Vendor path not found. Please execute "bin/composer.phar --no-dev install" on the command line in the web root.');
}

require __DIR__ . '/vendor/autoload.php';

// We assume that the index.php is called by a frontend process
// The value is set to "true" by default in App
$a = Factory\DependencyFactory::setUp('index', __DIR__, true);

$a->runFrontend();
