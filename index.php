<?php
/**
 * @file index.php
 * Friendica
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Factory;
use Friendica\Util\BasePath;

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	die('Vendor path not found. Please execute "bin/composer.phar --no-dev install" on the command line in the web root.');
}

require __DIR__ . '/vendor/autoload.php';

$basedir = BasePath::create(__DIR__, $_SERVER);
$configLoader = new Config\ConfigCacheLoader($basedir);
$config = Factory\ConfigFactory::createCache($configLoader);
$logger = Factory\LoggerFactory::create('index', $config);
$profiler = Factory\ProfilerFactory::create($config);

// We assume that the index.php is called by a frontend process
// The value is set to "true" by default in App
$a = new App($config, $logger, $profiler, false);

$a->runFrontend();
