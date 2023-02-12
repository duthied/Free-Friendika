<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

// Local configuration

/* If automatic system installation fails:
 *
 * Copy this file to local.config.php
 *
 * Why local.config.php? Because it contains sensitive information which could
 * give somebody complete control of your database. Apache's default
 * configuration will interpret any .php file as a script and won't show the values
 *
 * Then set the following for your MySQL installation
 */

return [
	'database' => [
		'hostname' => 'localhost',
		'username' => 'friendica',
		'password' => 'friendica',
		'database' => 'friendica',
		'charset' => 'utf8mb4',
	],

	// ****************************************************************
	// The configuration below will be overruled by the admin panel.
	// Changes made below will only have an effect if the database does
	// not contain any configuration for the friendica system.
	// ****************************************************************

	'config' => [
		'hostname' => 'friendica.local',
		'admin_email' => 'admin@friendica.local',
		'sitename' => 'Friendica Social Network',
		'register_policy' => \Friendica\Module\Register::OPEN,
		'register_text' => '',
	],
	'system' => [
		'default_timezone' => 'UTC',
		'language' => 'en',
		'ssl_policy' => \Friendica\App\BaseURL::SSL_POLICY_SELFSIGN,
		'url' => 'https://friendica.local',
		'urlpath' => '',
		// don't start unexpected worker.php processes during test!
		'worker_dont_fork' => true,
	],
];
