<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use Exception;
use Friendica\Core\Config\ValueObject\Cache;

/**
 * Helper for our main application structure for the life of this page.
 *
 * Primarily deals with the URL that got us here
 * and tries to make some sense of it, and
 * stores our page contents and config storage
 * and anything else that might need to be passed around
 * before we spit the page out.
 *
 */
interface AppHelper
{
	/**
	 * Set the profile owner ID
	 */
	public function setProfileOwner(int $owner_id);

	/**
	 * Get the profile owner ID
	 */
	public function getProfileOwner(): int;

	/**
	 * Set the timezone
	 *
	 * @param string $timezone A valid time zone identifier, see https://www.php.net/manual/en/timezones.php
	 */
	public function setTimeZone(string $timezone);

	/**
	 * Get the timezone name
	 */
	public function getTimeZone(): string;

	/**
	 * Set the contact ID
	 */
	public function setContactId(int $contact_id);

	/**
	 * Get the contact ID
	 */
	public function getContactId(): int;

	/**
	 * Set workerqueue information
	 *
	 * @param array<string,mixed> $queue
	 */
	public function setQueue(array $queue);

	/**
	 * Fetch workerqueue information
	 *
	 * @return array<string,mixed> Worker queue
	 */
	public function getQueue();

	/**
	 * Fetch a specific workerqueue field
	 *
	 * @param string $index Work queue record to fetch
	 *
	 * @return mixed|null Work queue item or NULL if not found
	 */
	public function getQueueValue(string $index);

	/**
	 * Returns the current theme name. May be overridden by the mobile theme name.
	 *
	 * @return string Current theme name or empty string in installation phase
	 * @throws Exception
	 */
	public function getCurrentTheme(): string;

	/**
	 * Returns the current mobile theme name.
	 *
	 * @return string Mobile theme name or empty string if installer
	 * @throws Exception
	 */
	public function getCurrentMobileTheme(): string;

	/**
	 * Setter for current theme name
	 *
	 * @param string $theme Name of current theme
	 */
	public function setCurrentTheme(string $theme);

	/**
	 * Setter for current mobile theme name
	 *
	 * @param string $theme Name of current mobile theme
	 */
	public function setCurrentMobileTheme(string $theme);

	public function setThemeInfoValue(string $index, $value);

	public function getThemeInfo();

	public function getThemeInfoValue(string $index, $default = null);

	/**
	 * Provide a sane default if nothing is chosen or the specified theme does not exist.
	 *
	 * @return string Current theme's stylesheet path
	 * @throws Exception
	 */
	public function getCurrentThemeStylesheetPath(): string;

	/**
	 * Returns the current config cache of this node
	 *
	 * @return Cache
	 */
	public function getConfigCache();

	/**
	 * The basepath of this app
	 *
	 * @return string Base path from configuration
	 */
	public function getBasePath(): string;

	public function redirect(string $toUrl);
}
