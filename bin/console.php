#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$a = new Friendica\App(dirname(__DIR__));
\Friendica\BaseObject::setApp($a);

(new Friendica\Core\Console($argv))->execute();
