<?php
/**
 * Copyright (C) 2010-2026, the Friendica project
 * SPDX-FileCopyrightText: 2010-2026 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
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
		'admin_email' => 'admin@friendica.local',
		'sitename' => 'Friendica Social Network',
		'register_policy' => \Friendica\Module\Register::OPEN,
		'register_text' => '',
	],
	'system' => [
		'default_timezone' => 'UTC',
		'language' => 'en',
		'url' => 'https://friendica.local',
		// don't start unexpected worker.php processes during test!
		'worker_dont_fork' => true,
	],
];
