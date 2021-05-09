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
 * This file declares the default values for the admin settings of Friendica.
 *
 * These values will be overridden by the admin settings page.
 *
 * Please don't edit this file directly as its content may change in the upcoming versions.
 *
 */

return [
	'config' => [
		// info (String)
		// Plaintext description of this node, used in the /friendica module.
		'info' => '',

		// register_policy (Constant)
		// Your choices are OPEN, APPROVE, or CLOSED.
		// Be certain to create your own personal account before setting CLOSED.
		// APPROVE requires you set system.admin_email to the email address of an
		// already registered person who can authorize and/or approve/deny the request.
		'register_policy' => \Friendica\Module\Register::CLOSED,

		// register_text (String)
		// Will be displayed prominently on the registration page.
		'register_text' => '',

		// sitename (String)
		// Displayed server name.
		'sitename' => 'Friendica Social Network',
	],
	'system' => [
		// account_abandon_days (Integer)
		// Will not waste system resources polling external sites for abandonded accounts.
		// Enter 0 for no time limit.
		'account_abandon_days' => 0,

		// addon (Comma-separated list)
		// Manual list of addons which are enabled on this system.
		'addon' => '',

		// add_missing_posts (boolean)
		// Checks for missing entries in "post", "post-thread" or "post-thread-user" and creates them
		'add_missing_posts' => false,

		// allowed_themes (Comma-separated list)
		// Themes users can change to in their settings.
		'allowed_themes' => 'frio,quattro,vier,duepuntozero,smoothly',

		// curl_timeout (Integer)
		// Value is in seconds. Set to 0 for unlimited (not recommended).
		'curl_timeout' =>  60,

		// dbclean (Boolean)
		// Remove old remote items, orphaned database records and old content from some other helper tables.
		'dbclean' => false,

		// dbclean-expire-days (Integer)
		// When the database cleanup is enabled, this defines the days after which remote items will be deleted.
		// Own items, and marked or filed items are always kept. 0 disables this behaviour.
		'dbclean-expire-days' => 0,

		// dbclean-expire-unclaimed (Integer)
		// When the database cleanup is enabled, this defines the days after which unclaimed remote items
		// (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general
		// lifespan value of remote items if set to 0.
		'dbclean-expire-unclaimed' => 90,

		// dbclean_expire_conversation (Integer)
		// The conversation data is used for ActivityPub and OStatus, as well as for debug purposes.
		// It should be safe to remove it after 14 days, default is 90 days.
		'dbclean_expire_conversation' => 90,

		// debugging (boolean)
		// Enable/Disable Debugging (logging)
		'debugging' => false,

		// default_timezone (String)
		// Choose a default timezone. See https://secure.php.net/manual/en/timezones.php
		// It only applies to timestamps for anonymous viewers.
		'default_timezone' => 'UTC',

		// directory (String)
		// URL of the global directory.
		'directory' => 'https://dir.friendica.social',

		// explicit_content (Boolean)
		// Set this to announce that your node is used mostly for explicit content that might not be suited for minors.
		'explicit_content' => false,

		// forbidden_nicknames (Comma-separated list)
		// Prevents users from registering the specified nicknames on this node.
		// Default value comprises classic role names from RFC 2142.
		'forbidden_nicknames' => 'info, marketing, sales, support, abuse, noc, security, postmaster, hostmaster, usenet, news, webmaster, www, uucp, ftp, root, sysop',

		// jpeg_quality (Integer)
		// Sets the ImageMagick quality level for JPEG images. Values ranges from 50 (awful) to 100 (near perfect).
		'jpeg_quality' => 100,

		// language (String)
		// System default languague, inluding admin-created user default language.
		// Two-letters ISO 639-1 code.
		'language' => 'en',

		// logfile (String)
		// The logfile for storing logs.
		// Can be a full path or a relative path to the Friendica home directory
		'logfile' => 'log/friendica.log',

		// loglevel (String)
		// The loglevel for all logs.
		// Has to be one of these values: emergency, alert, critical, error, warning, notice, info, debug
		'loglevel' => 'notice',

		// max_image_length (Integer)
		// An alternate way of limiting picture upload sizes.
		// Specify the maximum pixel  length that pictures are allowed to be (for non-square pictures, it will apply to the longest side).
		// Pictures longer than this length will be resized to be this length (on the longest side, the other side will be scaled appropriately).
		// If you don't want to set a maximum length, set to -1.
		'max_image_length' => -1,

		// maximagesize (Integer)
		// Maximum size in bytes of an uploaded photo.
		'maximagesize' => 800000,

		// maxloadavg (Integer)
		// Maximum system load before delivery and poll processes are deferred.
		'maxloadavg' => 20,

		// maxloadavg_frontend (Integer)
		// Maximum system load before the frontend quits service - default 50.
		'maxloadavg_frontend' => 50,

		// min_memory (Integer)
		// Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).
		'min_memory' => 0,

		// no_regfullname (Boolean)
		// Allow pseudonyms (true) or enforce a space between first name and last name in Full name, as an anti spam measure (false).
		'no_regfullname' => true,

		// optimize_tables (Boolean)
		// Periodically (once an hour) run an "optimize table" command for cache tables
		'optimize_tables' => false,

		// relay_deny_tags (String)
		// Comma separated list of tags that are rejected.
		'relay_deny_tags' => '',

		// relay_server (String)
		// Address of the relay server where public posts should be send to.
		'relay_server' => 'https://social-relay.isurf.ca',

		// relay_server_tags (String)
		// Comma separated list of tags for the "tags" subscription.
		'relay_server_tags' => '',

		// relay_user_tags (Boolean)
		// If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".
		'relay_user_tags' => true,

		// rino_encrypt (Integer)
		// Server-to-server private message encryption (RINO).
		// Encryption will only be provided if this setting is set to a non zero value on both servers.
		// Set to 0 to disable, 2 to enable, 1 is deprecated but won't need mcrypt.
		'rino_encrypt' => 2,

		// temppath (String)
		// Custom temporary file directory
		'temppath' => '',

		// theme (String)
		// System theme name.
		'theme' => 'frio',

		// url (String)
		// The fully-qualified URL of this Friendica node.
		// Used by the worker in a non-HTTP execution environment.
		'url' => '',
	],

	// Used in the admin settings to lock certain features
	'featurelock' => [
	],
];
