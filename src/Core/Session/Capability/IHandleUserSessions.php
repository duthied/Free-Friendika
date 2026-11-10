<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Session\Capability;

/**
 * This interface handles UserSessions, which is directly extended from the global Session interface
 */
interface IHandleUserSessions extends IHandleSessions
{
	/**
	 * Returns the user id of locally logged-in user or false.
	 *
	 * @return int|bool user id or false
	 */
	public function getLocalUserId();

	/**
	 * Returns the user nickname of locally logged-in user.
	 *
	 * @return string|false User's nickname or false
	 */
	public function getLocalUserNickname();

	/**
	 * Returns the public contact id of logged-in user or false.
	 *
	 * @return int|bool public contact id or false
	 */
	public function getPublicContactId();

	/**
	 * Returns public contact id of authenticated site visitor or false
	 *
	 * @return int|bool visitor_id or false
	 */
	public function getRemoteUserId();

	/**
	 * Return the user contact ID of a visitor for the given user ID they are visiting
	 *
	 * @param int $uid User ID
	 *
	 * @return int
	 */
	public function getRemoteContactID(int $uid): int;

	/**
	 * Returns User ID for given contact ID of the visitor
	 *
	 * @param int $cid Contact ID
	 *
	 * @return int User ID for given contact ID of the visitor
	 */
	public function getUserIDForVisitorContactID(int $cid): int;

	/**
	 * Returns the account URL of the currently logged in user
	 *
	 * @return string
	 */
	public function getMyUrl(): string;

	/**
	 * Returns if the current visitor is a local user
	 *
	 * @return bool "true" when visitor is a local user
	 */
	public function isAuthenticated(): bool;

	/**
	 * Check if current user has admin role.
	 *
	 * @return bool true if user is an admin
	 */
	public function isSiteAdmin(): bool;

	/**
	 * Check if current user is a moderator.
	 *
	 * @return bool true if user is a moderator
	 */
	public function isModerator(): bool;

	/**
	 * Returns if the current visitor is a verified remote user
	 *
	 * @return bool "true" when visitor is a verified remote user
	 */
	public function isVisitor(): bool;

	/**
	 * Returns if the current visitor is an unauthenticated user
	 *
	 * @return bool "true" when visitor is an unauthenticated user
	 */
	public function isUnauthenticated(): bool;

	/**
	 * Returns User ID of the managed user in case it's a different identity
	 *
	 * @return int|bool uid of the manager or false
	 */
	public function getSubManagedUserId();

	/**
	 * Sets the User ID of the managed user in case it's a different identity
	 *
	 * @param int $managed_uid The user id of the managing user
	 */
	public function setSubManagedUserId(int $managed_uid): void;

	/**
	 * Set the session variable that contains the contact IDs for the visitor's contact URL
	 *
	 * @param string $my_url
	 */
	public function setVisitorsContacts(string $my_url);
}
