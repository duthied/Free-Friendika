<?php

namespace Friendica\Module;

use Exception;
use Friendica\BaseModule;
use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Notification;
use Friendica\Network\HTTPException\ForbiddenException;

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

	public static function post(array $parameters = [])
	{
		$request_id = DI::args()->get(1);

		if ($request_id === 'all') {
			return;
		}

		if ($request_id) {
			$intro = DI::intro()->selectFirst(['id' => $request_id, 'uid' => local_user()]);

			switch ($_POST['submit']) {
				case DI::l10n()->t('Discard'):
					$intro->discard();
					break;
				case DI::l10n()->t('Ignore'):
					$intro->ignore();
					break;
			}

			DI::baseUrl()->redirect('notifications/intros');
		}
	}

	public static function rawContent(array $parameters = [])
	{
		// If the last argument of the query is NOT json, return
		if (DI::args()->get(DI::args()->getArgc() - 1) !== 'json') {
			return;
		}

		System::jsonExit(static::getNotifications()['notifs'] ?? []);
	}

	/**
	 * Shows the printable result of notifications for a specific tab
	 *
	 * @param string $header    The notification header
	 * @param array  $content   The array with the notifications
	 * @param string $noContent The string in case there are no notifications
	 * @param array  $showLink  The possible links at the top
	 *
	 * @return string The rendered output
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected static function printContent(string $header, array $content, string $noContent, array $showLink)
	{
		// Get the nav tabs for the notification pages
		$tabs = self::getTabs();

		// Set the pager
		$pager = new Pager(DI::args()->getQueryString(), self::ITEMS_PER_PAGE);

		$notif_tpl = Renderer::getMarkupTemplate('notifications/notifications.tpl');
		return Renderer::replaceMacros($notif_tpl, [
			'$notif_header'    => $header ?? DI::l10n()->t('Notifications'),
			'$tabs'            => $tabs,
			'$notif_content'   => $content,
			'$notif_nocontent' => $noContent,
			'$notif_show_lnk'  => $showLink,
			'$notif_paginate'  => $pager->renderMinimal(count($content))
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
