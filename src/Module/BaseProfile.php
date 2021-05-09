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
 */

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

class BaseProfile extends BaseModule
{
	/**
	 * Returns the HTML for the profile pages tabs
	 *
	 * @param App    $a
	 * @param string $current
	 * @param bool   $is_owner
	 * @param string $nickname
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getTabsHTML(App $a, string $current, bool $is_owner, string $nickname = null)
	{
		if (is_null($nickname)) {
			$nickname = $a->user['nickname'];
		}

		$baseProfileUrl = DI::baseUrl() . '/profile/' . $nickname;

		$tabs = [
			[
				'label' => DI::l10n()->t('Profile'),
				'url'   => $baseProfileUrl . '/profile',
				'sel'   => $current == 'profile' ? 'active' : '',
				'title' => DI::l10n()->t('Profile Details'),
				'id'    => 'profile-tab',
				'accesskey' => 'r',
			],
			[
				'label' => DI::l10n()->t('Status'),
				'url'   => $baseProfileUrl . '/status',
				'sel'   => $current == 'status' ? 'active' : '',
				'title' => DI::l10n()->t('Status Messages and Posts'),
				'id'    => 'status-tab',
				'accesskey' => 'm',
			],
			[
				'label' => DI::l10n()->t('Photos'),
				'url'   => DI::baseUrl() . '/photos/' . $nickname,
				'sel'   => $current == 'photos' ? 'active' : '',
				'title' => DI::l10n()->t('Photo Albums'),
				'id'    => 'photo-tab',
				'accesskey' => 'h',
			],
			[
				'label' => DI::l10n()->t('Videos'),
				'url'   => DI::baseUrl() . '/videos/' . $nickname,
				'sel'   => $current == 'videos' ? 'active' : '',
				'title' => DI::l10n()->t('Videos'),
				'id'    => 'video-tab',
				'accesskey' => 'v',
			],
		];

		// the calendar link for the full featured events calendar
		if ($is_owner && $a->theme_events_in_profile) {
			$tabs[] = [
				'label' => DI::l10n()->t('Events'),
				'url'   => DI::baseUrl() . '/events',
				'sel'   => $current == 'events' ? 'active' : '',
				'title' => DI::l10n()->t('Events and Calendar'),
				'id'    => 'events-tab',
				'accesskey' => 'e',
			];
			// if the user is not the owner of the calendar we only show a calendar
			// with the public events of the calendar owner
		} elseif (!$is_owner) {
			$tabs[] = [
				'label' => DI::l10n()->t('Events'),
				'url'   => DI::baseUrl() . '/cal/' . $nickname,
				'sel'   => $current == 'cal' ? 'active' : '',
				'title' => DI::l10n()->t('Events and Calendar'),
				'id'    => 'events-tab',
				'accesskey' => 'e',
			];
		}

		if ($is_owner) {
			$tabs[] = [
				'label' => DI::l10n()->t('Personal Notes'),
				'url'   => DI::baseUrl() . '/notes',
				'sel'   => $current == 'notes' ? 'active' : '',
				'title' => DI::l10n()->t('Only You Can See This'),
				'id'    => 'notes-tab',
				'accesskey' => 't',
			];
		}

		if (empty($a->profile['hide-friends'])) {
			$tabs[] = [
				'label' => DI::l10n()->t('Contacts'),
				'url'   => $baseProfileUrl . '/contacts',
				'sel'   => $current == 'contacts' ? 'active' : '',
				'title' => DI::l10n()->t('Contacts'),
				'id'    => 'viewcontacts-tab',
				'accesskey' => 'k',
			];
		}

		if (DI::session()->get('new_member') && $is_owner) {
			$tabs[] = [
				'label' => DI::l10n()->t('Tips for New Members'),
				'url'   => DI::baseUrl() . '/newmember',
				'sel'   => false,
				'title' => DI::l10n()->t('Tips for New Members'),
				'id'    => 'newmember-tab',
			];
		}

		$arr = ['is_owner' => $is_owner, 'nickname' => $nickname, 'tab' => $current, 'tabs' => $tabs];

		Hook::callAll('profile_tabs', $arr);

		$tpl = Renderer::getMarkupTemplate('common_tabs.tpl');

		return Renderer::replaceMacros($tpl, ['$tabs' => $arr['tabs']]);
	}
}
