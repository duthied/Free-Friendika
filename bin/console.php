#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Friendica\Factory;

$a = Factory\DependencyFactory::setUp('console', dirname(__DIR__));
\Friendica\BaseObject::setApp($a);

(new Friendica\Core\Console($argv))->execute();
