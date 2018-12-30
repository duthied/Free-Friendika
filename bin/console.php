#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Friendica\Core\Logger;

$logger = Logger::create('console');

$a = new Friendica\App(dirname(__DIR__), $logger);
\Friendica\BaseObject::setApp($a);

(new Friendica\Core\Console($argv))->execute();
