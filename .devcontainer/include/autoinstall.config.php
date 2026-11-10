<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

return [
	'database' => [
		'hostname' => '${MYSQL_HOST}',
		'username' => '${MYSQL_USER}',
		'password' => '${MYSQL_PASSWORD}',
		'database' => '${MYSQL_DATABASE}',
		'charset' => 'utf8mb4',
	],

	// ****************************************************************
	// The configuration below will be overruled by the admin panel.
	// Changes made below will only have an effect if the database does
	// not contain any configuration for the friendica system.
	// ****************************************************************

	'config' => [
		'admin_email' => 'admin@${ServerAlias}',
		'sitename' => 'Friendica Social Network',
		'register_policy' => \Friendica\Module\Register::OPEN,
		'register_text' => '',
		'php' => '${FRIENDICA_PHP_PATH}',
	],
	'system' => [
		'default_timezone' => 'UTC',
		'language' => 'en',
		'basepath' => '${workspaceFolder}',
		'url' => 'http://${ServerName}:${ServerPort}'
	],
];
