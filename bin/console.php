#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = new \Dice\Dice();
$dice = $dice->addRules(include __DIR__ . '/../static/dependencies.config.php');

(new Friendica\Core\Console($dice, $argv))->execute();
