<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Module;

use Exception;
use Friendica\BaseModule;
use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Object\Notification\Notification;

/**
 * Base Module for each tab of the notification display
 *
 * General possibility to print it as JSON as well
 */
abstract class BaseNotifications extends BaseModule
{
	/** @var array Array of URL parameters */
	const URL_TYPES = [
		Notification::NETWORK  => 'network',
		Notification::SYSTEM   => 'system',
		Notification::HOME     => 'home',
		Notification::PERSONAL => 'personal',
		Notification::INTRO    => 'intros',
	];

	/** @var array Array of the allowed notifications and their printable name */
	const PRINT_TYPES = [
		Notification::NETWORK  => 'Network',
		Notification::SYSTEM   => 'System',
		Notification::HOME     => 'Home',
		Notification::PERSONAL => 'Personal',
		Notification::INTRO    => 'Introductions',
	];

	/** @var array The array of access keys for notification pages */
	const ACCESS_KEYS = [
		Notification::NETWORK  => 'w',
		Notification::SYSTEM   => 'y',
		Notification::HOME     => 'h',
		Notification::PERSONAL => 'r',
		Notification::INTRO    => 'i',
	];

	/** @var int The default count of items per page */
	const ITEMS_PER_PAGE = 20;
	/** @var int The default limit of notifications per page */
	const DEFAULT_PAGE_LIMIT = 80;

	/** @var boolean True, if ALL entries should get shown */
	protected static $showAll;
	/** @var int The determined start item of the current page */
	protected static $firstItemNum;

	/**
	 * Collects all notifications from the backend
	 *
	 * @return array The determined notification array
	 *               ['header', 'notifications']
	 */
	abstract public static function getNotifications();

	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			throw new ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$page = ($_REQUEST['page'] ?? 0) ?: 1;

		self::$firstItemNum = ($page * self::ITEMS_PER_PAGE) - self::ITEMS_PER_PAGE;
		self::$showAll      = ($_REQUEST['show'] ?? '') === 'all';
	}

	public static function rawContent(array $parameters = [])
	{
		// If the last argument of the query is NOT json, return
		if (DI::args()->get(DI::args()->getArgc() - 1) !== 'json') {
			return;
		}

		// Set the pager
		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), self::ITEMS_PER_PAGE);

		// Add additional informations (needed for json output)
		$notifications = [
			'notifications' => static::getNotifications(),
			'items_page'    => $pager->getItemsPerPage(),
			'page'          => $pager->getPage(),
		];

		System::jsonExit($notifications);
	}

	/**
	 * Shows the printable result of notifications for a specific tab
	 *
	 * @param string $header        The notification header
	 * @param array  $notifications The array with the notifications
	 * @param string $noContent     The string in case there are no notifications
	 * @param array  $showLink      The possible links at the top
	 *
	 * @return string The rendered output
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected static function printContent(string $header, array $notifications, string $noContent, array $showLink)
	{
		// Get the nav tabs for the notification pages
		$tabs = self::getTabs();

		// Set the pager
		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), self::ITEMS_PER_PAGE);

		$notif_tpl = Renderer::getMarkupTemplate('notifications/notifications.tpl');
		return Renderer::replaceMacros($notif_tpl, [
			'$header'        => $header ?? DI::l10n()->t('Notifications'),
			'$tabs'          => $tabs,
			'$notifications' => $notifications,
			'$noContent'     => $noContent,
			'$showLink'      => $showLink,
			'$paginate'      => $pager->renderMinimal(count($notifications))
		]);
	}

	/**
	 * List of pages for the Notifications TabBar
	 *
	 * @return array with with notifications TabBar data
	 * @throws Exception
	 */
	private static function getTabs()
	{
		$selected = DI::args()->get(1, '');

		$tabs = [];

		foreach (self::URL_TYPES as $type => $url) {
			$tabs[] = [
				'label'     => DI::l10n()->t(self::PRINT_TYPES[$type]),
				'url'       => 'notifications/' . $url,
				'sel'       => (($selected == $url) ? 'active' : ''),
				'id'        => $type . '-tab',
				'accesskey' => self::ACCESS_KEYS[$type],
			];
		}

		return $tabs;
	}
}
