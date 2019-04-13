<?php

// Local configuration

// If you're unsure about what any of the config keys below do, please check the config/defaults.config.php for detailed
// documentation of their data type and behavior.

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
		'php_path' => '/usr/bin/php',
		'admin_email' => '',
		'sitename' => 'Friendica Social Network',
		'hostname' => 'friendica.local',
		'register_policy' => \Friendica\Module\Register::OPEN,
		'max_import_size' => 200000,
	],
	'system' => [
		'urlpath' => 'test',
		'url' => 'https://friendica.local/test',
		'ssl_policy' => 1,
		'basepath' => '/vagrant',
		'default_timezone' => 'America/Los_Angeles',
		'language' => 'en',
		'debugging' => true,
		'logfile' => 'friendica.log',
		'loglevel' => 'info',
	],
];
