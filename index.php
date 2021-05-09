<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Dice\Dice;

$start_time = microtime(true);

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	die('Vendor path not found. Please execute "bin/composer.phar --no-dev install" on the command line in the web root.');
}

require __DIR__ . '/vendor/autoload.php';

$dice = (new Dice())->addRules(include __DIR__ . '/static/dependencies.config.php');
$dice = $dice->addRule(Friendica\App\Mode::class, ['call' => [['determineRunMode', [false, $_SERVER], Dice::CHAIN_CALL]]]);

\Friendica\DI::init($dice);

$a = \Friendica\DI::app();

\Friendica\DI::mode()->setExecutor(\Friendica\App\Mode::INDEX);

$a->runFrontend(
	$dice->create(\Friendica\App\Module::class),
	$dice->create(\Friendica\App\Router::class),
	$dice->create(\Friendica\Core\PConfig\IPConfig::class),
	$dice->create(\Friendica\Security\Authentication::class),
	$dice->create(\Friendica\App\Page::class),
	$start_time
);
