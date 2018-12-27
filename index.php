<?php
/**
 * @file index.php
 * Friendica
 */

require __DIR__ . '/vendor/autoload.php';

// We assume that the index.php is called by a frontend process
// The value is set to "true" by default in App
$a = new Friendica\App(__DIR__, false);

$a->runFrontend();
