#!/usr/bin/env php
<?php

use Dice\Dice;

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = (new Dice())->addRules(include __DIR__ . '/../static/dependencies.config.php');

(new Friendica\Core\Console($dice, $argv))->execute();
