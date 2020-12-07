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

namespace Friendica\Module\Settings;

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\Theme;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseSettings;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Module to update user settings
 */
class Display extends BaseSettings
{
	public static function post(array $parameters = [])
	{
		if (!local_user() || !empty(DI::app()->user['uid']) && DI::app()->user['uid'] != local_user()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		self::checkFormSecurityTokenRedirectOnError('/settings/display', 'settings_display');

		$theme              = !empty($_POST['theme'])              ? Strings::escapeTags(trim($_POST['theme']))        : DI::app()->user['theme'];
		$mobile_theme       = !empty($_POST['mobile_theme'])       ? Strings::escapeTags(trim($_POST['mobile_theme'])) : '';
		$nosmile            = !empty($_POST['nosmile'])            ? intval($_POST['nosmile'])            : 0;
		$first_day_of_week  = !empty($_POST['first_day_of_week'])  ? intval($_POST['first_day_of_week'])  : 0;
		$infinite_scroll    = !empty($_POST['infinite_scroll'])    ? intval($_POST['infinite_scroll'])    : 0;
		$no_auto_update     = !empty($_POST['no_auto_update'])     ? intval($_POST['no_auto_update'])     : 0;
		$no_smart_threading = !empty($_POST['no_smart_threading']) ? intval($_POST['no_smart_threading']) : 0;
		$hide_dislike       = !empty($_POST['hide_dislike'])       ? intval($_POST['hide_dislike'])       : 0;
		$browser_update     = !empty($_POST['browser_update'])     ? intval($_POST['browser_update'])     : 0;
		if ($browser_update != -1) {
			$browser_update = $browser_update * 1000;
			if ($browser_update < 10000) {
				$browser_update = 10000;
			}
		}

		$itemspage_network = !empty($_POST['itemspage_network']) ?
			intval($_POST['itemspage_network']) :
			DI::config()->get('system', 'itemspage_network');
		if ($itemspage_network > 100) {
			$itemspage_network = 100;
		}
		$itemspage_mobile_network = !empty($_POST['itemspage_mobile_network']) ?
			intval($_POST['itemspage_mobile_network']) :
			DI::config()->get('system', 'itemspage_network_mobile');
		if ($itemspage_mobile_network > 100) {
			$itemspage_mobile_network = 100;
		}

		if ($mobile_theme !== '') {
			DI::pConfig()->set(local_user(), 'system', 'mobile_theme', $mobile_theme);
		}

		DI::pConfig()->set(local_user(), 'system', 'itemspage_network'       , $itemspage_network);
		DI::pConfig()->set(local_user(), 'system', 'itemspage_mobile_network', $itemspage_mobile_network);
		DI::pConfig()->set(local_user(), 'system', 'update_interval'         , $browser_update);
		DI::pConfig()->set(local_user(), 'system', 'no_auto_update'          , $no_auto_update);
		DI::pConfig()->set(local_user(), 'system', 'no_smilies'              , $nosmile);
		DI::pConfig()->set(local_user(), 'system', 'infinite_scroll'         , $infinite_scroll);
		DI::pConfig()->set(local_user(), 'system', 'no_smart_threading'      , $no_smart_threading);
		DI::pConfig()->set(local_user(), 'system', 'hide_dislike'            , $hide_dislike);
		DI::pConfig()->set(local_user(), 'system', 'first_day_of_week'       , $first_day_of_week);

		if (in_array($theme, Theme::getAllowedList())) {
			if ($theme == DI::app()->user['theme']) {
				// call theme_post only if theme has not been changed
				if (($themeconfigfile = Theme::getConfigFile($theme)) !== null) {
					require_once $themeconfigfile;
					theme_post(DI::app());
				}
			} else {
				DBA::update('user', ['theme' => $theme], ['uid' => local_user()]);
			}
		} else {
			notice(DI::l10n()->t('The theme you chose isn\'t available.'));
		}

		Hook::callAll('display_settings_post', $_POST);

		DI::baseUrl()->redirect('settings/display');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		if (!local_user()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$default_theme = DI::config()->get('system', 'theme');
		if (!$default_theme) {
			$default_theme = 'default';
		}

		$default_mobile_theme = DI::config()->get('system', 'mobile-theme');
		if (!$default_mobile_theme) {
			$default_mobile_theme = 'none';
		}

		$allowed_themes = Theme::getAllowedList();

		$themes = [];
		$mobile_themes = ["---" => DI::l10n()->t('No special theme for mobile devices')];
		foreach ($allowed_themes as $theme) {
			$is_experimental = file_exists('view/theme/' . $theme . '/experimental');
			$is_unsupported  = file_exists('view/theme/' . $theme . '/unsupported');
			$is_mobile       = file_exists('view/theme/' . $theme . '/mobile');
			if (!$is_experimental || (DI::config()->get('experimentals', 'exp_themes') || is_null(DI::config()->get('experimentals', 'exp_themes')))) {
				$theme_name = ucfirst($theme);
				if ($is_unsupported) {
					$theme_name = DI::l10n()->t('%s - (Unsupported)', $theme_name);
				} elseif ($is_experimental) {
					$theme_name = DI::l10n()->t('%s - (Experimental)', $theme_name);
				}

				if ($is_mobile) {
					$mobile_themes[$theme] = $theme_name;
				} else {
					$themes[$theme] = $theme_name;
				}
			}
		}

		$theme_selected        = DI::app()->user['theme'] ?: $default_theme;
		$mobile_theme_selected = Session::get('mobile-theme', $default_mobile_theme);

		$itemspage_network = intval(DI::pConfig()->get(local_user(), 'system', 'itemspage_network'));
		$itemspage_network = (($itemspage_network > 0 && $itemspage_network < 101) ? $itemspage_network : DI::config()->get('system', 'itemspage_network'));
		$itemspage_mobile_network = intval(DI::pConfig()->get(local_user(), 'system', 'itemspage_mobile_network'));
		$itemspage_mobile_network = (($itemspage_mobile_network > 0 && $itemspage_mobile_network < 101) ? $itemspage_mobile_network : DI::config()->get('system', 'itemspage_network_mobile'));

		$browser_update = intval(DI::pConfig()->get(local_user(), 'system', 'update_interval'));
		if (intval($browser_update) != -1) {
			$browser_update = (($browser_update == 0) ? 40 : $browser_update / 1000); // default if not set: 40 seconds
		}

		$no_auto_update     = DI::pConfig()->get(local_user(), 'system', 'no_auto_update', 0);
		$nosmile            = DI::pConfig()->get(local_user(), 'system', 'no_smilies', 0);
		$infinite_scroll    = DI::pConfig()->get(local_user(), 'system', 'infinite_scroll', 0);
		$no_smart_threading = DI::pConfig()->get(local_user(), 'system', 'no_smart_threading', 0);
		$hide_dislike       = DI::pConfig()->get(local_user(), 'system', 'hide_dislike', 0);

		$first_day_of_week = DI::pConfig()->get(local_user(), 'system', 'first_day_of_week', 0);
		$weekdays = [0 => DI::l10n()->t("Sunday"), 1 => DI::l10n()->t("Monday")];

		$theme_config = '';
		if ($themeconfigfile = Theme::getConfigFile($theme_selected)) {
			require_once $themeconfigfile;
			$theme_config = theme_content(DI::app());
		}

		$tpl = Renderer::getMarkupTemplate('settings/display.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$ptitle'         => DI::l10n()->t('Display Settings'),
			'$submit'         => DI::l10n()->t('Save Settings'),
			'$d_tset'         => DI::l10n()->t('General Theme Settings'),
			'$d_ctset'        => DI::l10n()->t('Custom Theme Settings'),
			'$d_cset'         => DI::l10n()->t('Content Settings'),
			'$stitle'         => DI::l10n()->t('Theme settings'),
			'$calendar_title' => DI::l10n()->t('Calendar'),

			'$form_security_token' => self::getFormSecurityToken('settings_display'),
			'$baseurl' => DI::baseUrl()->get(true),
			'$uid'     => local_user(),

			'$theme'	    => ['theme', DI::l10n()->t('Display Theme:'), $theme_selected, '', $themes, true],
			'$mobile_theme'	=> ['mobile_theme', DI::l10n()->t('Mobile Theme:'), $mobile_theme_selected, '', $mobile_themes, false],
			'$theme_config' => $theme_config,

			'$itemspage_network'        => ['itemspage_network'       , DI::l10n()->t('Number of items to display per page:'), $itemspage_network, DI::l10n()->t('Maximum of 100 items')],
			'$itemspage_mobile_network' => ['itemspage_mobile_network', DI::l10n()->t('Number of items to display per page when viewed from mobile device:'), $itemspage_mobile_network, DI::l10n()->t('Maximum of 100 items')],
			'$ajaxint'                  => ['browser_update'          , DI::l10n()->t('Update browser every xx seconds'), $browser_update, DI::l10n()->t('Minimum of 10 seconds. Enter -1 to disable it.')],
			'$no_auto_update'           => ['no_auto_update'          , DI::l10n()->t('Automatic updates only at the top of the post stream pages'), $no_auto_update, DI::l10n()->t('Auto update may add new posts at the top of the post stream pages, which can affect the scroll position and perturb normal reading if it happens anywhere else the top of the page.')],
			'$nosmile'	                => ['nosmile'                 , DI::l10n()->t('Don\'t show emoticons'), $nosmile, DI::l10n()->t('Normally emoticons are replaced with matching symbols. This setting disables this behaviour.')],
			'$infinite_scroll'          => ['infinite_scroll'         , DI::l10n()->t('Infinite scroll'), $infinite_scroll, DI::l10n()->t('Automatic fetch new items when reaching the page end.')],
			'$no_smart_threading'       => ['no_smart_threading'      , DI::l10n()->t('Disable Smart Threading'), $no_smart_threading, DI::l10n()->t('Disable the automatic suppression of extraneous thread indentation.')],
			'$hide_dislike'             => ['hide_dislike'            , DI::l10n()->t('Hide the Dislike feature'), $hide_dislike, DI::l10n()->t('Hides the Dislike button and dislike reactions on posts and comments.')],

			'$first_day_of_week' => ['first_day_of_week', DI::l10n()->t('Beginning of week:'), $first_day_of_week, '', $weekdays, false],
		]);

		return $o;
	}
}
