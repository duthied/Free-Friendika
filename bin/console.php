#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Friendica\Core\Config\Cache;
use Friendica\Factory;
use Friendica\Util\BasePath;

$basedir = BasePath::create(dirname(__DIR__), $_SERVER);
$configLoader = new Cache\ConfigCacheLoader($basedir);
$configCache = Factory\ConfigFactory::createCache($configLoader);
Factory\DBFactory::init($configCache, $_SERVER);
$config = Factory\ConfigFactory::createConfig($configCache);
// needed to call PConfig::init()
Factory\ConfigFactory::createPConfig($configCache);
$logger = Factory\LoggerFactory::create('console', $config);
$profiler = Factory\ProfilerFactory::create($logger, $config);

$a = new Friendica\App($config, $logger, $profiler);
\Friendica\BaseObject::setApp($a);

(new Friendica\Core\Console($argv))->execute();
