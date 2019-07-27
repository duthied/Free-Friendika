<?php
/**
 * @file index.php
 * Friendica
 */

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	die('Vendor path not found. Please execute "bin/composer.phar --no-dev install" on the command line in the web root.');
}

require __DIR__ . '/vendor/autoload.php';

$dice = new \Dice\Dice();
$dice = $dice->addRules(include __DIR__ . '/static/dependencies.config.php');

\Friendica\BaseObject::setDependencyInjection($dice);

$a = \Friendica\BaseObject::getApp();

$a->runFrontend();
