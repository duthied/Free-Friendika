#!/usr/bin/env php
<?php

use Dice\Dice;
use Psr\Log\LoggerInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = (new Dice())->addRules(include __DIR__ . '/../static/dependencies.config.php');
$dice = $dice->addRule(LoggerInterface::class,['constructParams' => ['console']]);

(new Friendica\Core\Console($dice, $argv))->execute();
