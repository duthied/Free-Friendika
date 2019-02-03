#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Friendica\Core\Config;
use Friendica\Factory;
use Friendica\Util\BasePath;

$basedir = BasePath::create(dirname(__DIR__));
$configLoader = new Config\ConfigCacheLoader($basedir);
$config = Factory\ConfigFactory::createCache($configLoader);
$logger = Factory\LoggerFactory::create('console', $config);

$a = new Friendica\App($config, $logger);
\Friendica\BaseObject::setApp($a);

(new Friendica\Core\Console($argv))->execute();
