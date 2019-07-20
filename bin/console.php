#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Friendica\Factory;

$dice = new \Dice\Dice();
$dice = $dice->addRules(include __DIR__ . '/../static/dependencies.config.php');

$a = Factory\DependencyFactory::setUp('console', $dice);
\Friendica\BaseObject::setApp($a);

(new Friendica\Core\Console($argv))->execute();
