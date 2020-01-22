<?php

namespace Friendica\Module;

use Exception;
use Friendica\BaseModule;
use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Notify;
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
		Notify::NETWORK  => 'network',
		Notify::SYSTEM   => 'system',
		Notify::HOME     => 'home',
		Notify::PERSONAL => 'personal',
		Notify::INTRO    => 'intros',
	];

	/** @var array Array of the allowed notifies and their printable name */
	const PRINT_TYPES = [
		Notify::NETWORK  => 'Network',
		Notify::SYSTEM   => 'System',
		Notify::HOME     => 'Home',
		Notify::PERSONAL => 'Personal',
		Notify::INTRO    => 'Introductions',
	];

	/** @var array The array of access keys for notify pages */
	const ACCESS_KEYS = [
		Notify::NETWORK  => 'w',
		Notify::SYSTEM   => 'y',
		Notify::HOME     => 'h',
		Notify::PERSONAL => 'r',
		Notify::INTRO    => 'i',
	];

	/** @var int The default count of items per page */
	const ITEMS_PER_PAGE = 20;

	/** @var boolean True, if ALL entries should get shown */
	protected static $showAll;
	/** @var int The determined start item of the current page */
	protected static $firstItemNum;

	/**
	 * Collects all notifies from the backend
	 *
	 * @return array The determined notification array
	 *               ['header', 'notifs']
	 */
	abstract public static function getNotifies();

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

		System::jsonExit(static::getNotifies()['notifs'] ?? []);
	}

	/**
	 * Shows the printable result of notifications for a specific tab
	 *
	 * @param string $notif_header    The notification header
	 * @param array  $notif_content   The array with the notifications
	 * @param string $notif_nocontent The string in case there are no notifications
	 * @param array  $notif_show_lnk  The possible links at the top
	 *
	 * @return string The rendered output
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected static function printContent(string $notif_header, array $notif_content, string $notif_nocontent, array $notif_show_lnk)
	{
		// Get the nav tabs for the notification pages
		$tabs = self::getTabs();

		// Set the pager
		$pager = new Pager(DI::args()->getQueryString(), self::ITEMS_PER_PAGE);

		$notif_tpl = Renderer::getMarkupTemplate('notifications.tpl');
		return Renderer::replaceMacros($notif_tpl, [
			'$notif_header'    => $notif_header ?? DI::l10n()->t('Notifications'),
			'$tabs'            => $tabs,
			'$notif_content'   => $notif_content,
			'$notif_nocontent' => $notif_nocontent,
			'$notif_show_lnk'  => $notif_show_lnk,
			'$notif_paginate'  => $pager->renderMinimal(count($notif_content))
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
