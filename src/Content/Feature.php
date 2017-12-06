<?php
/**
 * @file src/Content/Feature.php
 * @brief Features management
 */
namespace Friendica\Content;

use Friendica\Core\Config;
use Friendica\Core\PConfig;

require_once 'include/plugin.php';

class Feature
{
	/**
	 * @brief check if feature is enabled
	 *
	 * @param integer $uid     user id
	 * @param string  $feature feature
	 * @return boolean
	 */
	public static function isEnabled($uid, $feature)
	{
		$x = Config::get('feature_lock', $feature, false);

		if (is_null($x)) {
			$x = PConfig::get($uid, 'feature', $feature, false);
		}

		if (is_null($x)) {
			$x = Config::get('feature', $feature, false);
		}

		if (is_null($x)) {
			$x = self::getDefault($feature);
		}

		$arr = array('uid' => $uid, 'feature' => $feature, 'enabled' => $x);
		call_hooks('isEnabled', $arr);
		return($arr['enabled']);
	}

	/**
	 * @brief check if feature is enabled or disabled by default
	 *
	 * @param string $feature feature
	 * @return boolean
	 */
	private static function getDefault($feature)
	{
		$f = self::get();
		foreach ($f as $cat) {
			foreach ($cat as $feat) {
				if (is_array($feat) && $feat[0] === $feature) {
					return $feat[3];
				}
			}
		}
		return false;
	}

	/**
	 * @brief Get a list of all available features
	 *
	 * The array includes the setting group, the setting name,
	 * explainations for the setting and if it's enabled or disabled
	 * by default
	 *
	 * @param bool $filtered True removes any locked features
	 *
	 * @return array
	 */
	public static function get($filtered = true)
	{
		$arr = array(

			// General
			'general' => array(
				t('General Features'),
				//array('expire',         t('Content Expiration'),		t('Remove old posts/comments after a period of time')),
				array('multi_profiles', t('Multiple Profiles'),			t('Ability to create multiple profiles'), false, Config::get('feature_lock', 'multi_profiles', false)),
				array('photo_location', t('Photo Location'),			t('Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'), false, Config::get('feature_lock', 'photo_location', false)),
				array('export_calendar', t('Export Public Calendar'),		t('Ability for visitors to download the public calendar'), false, Config::get('feature_lock', 'export_calendar', false)),
			),

			// Post composition
			'composition' => array(
				t('Post Composition Features'),
				array('preview',	t('Post Preview'),			t('Allow previewing posts and comments before publishing them'), false, Config::get('feature_lock', 'preview', false)),
				array('aclautomention',	t('Auto-mention Forums'),		t('Add/remove mention when a forum page is selected/deselected in ACL window.'), false, Config::get('feature_lock', 'aclautomention', false)),
			),

			// Network sidebar widgets
			'widgets' => array(
				t('Network Sidebar Widgets'),
				array('archives',	t('Search by Date'),			t('Ability to select posts by date ranges'), false, Config::get('feature_lock', 'archives', false)),
				array('forumlist_widget', t('List Forums'),			t('Enable widget to display the forums your are connected with'), true, Config::get('feature_lock', 'forumlist_widget', false)),
				array('groups',		t('Group Filter'),			t('Enable widget to display Network posts only from selected group'), false, Config::get('feature_lock', 'groups', false)),
				array('networks',	t('Network Filter'),			t('Enable widget to display Network posts only from selected network'), false, Config::get('feature_lock', 'networks', false)),
				array('savedsearch',	t('Saved Searches'),			t('Save search terms for re-use'), false, Config::get('feature_lock', 'savedsearch', false)),
			),

			// Network tabs
			'net_tabs' => array(
				t('Network Tabs'),
				array('personal_tab',	t('Network Personal Tab'),		t('Enable tab to display only Network posts that you\'ve interacted on'), false, Config::get('feature_lock', 'personal_tab', false)),
				array('new_tab',	t('Network New Tab'),			t('Enable tab to display only new Network posts (from the last 12 hours)'), false, Config::get('feature_lock', 'new_tab', false)),
				array('link_tab',	t('Network Shared Links Tab'),		t('Enable tab to display only Network posts with links in them'), false, Config::get('feature_lock', 'link_tab', false)),
			),

			// Item tools
			'tools' => array(
				t('Post/Comment Tools'),
				array('multi_delete',	t('Multiple Deletion'),			t('Select and delete multiple posts/comments at once'), false, Config::get('feature_lock', 'multi_delete', false)),
				array('edit_posts',	t('Edit Sent Posts'),			t('Edit and correct posts and comments after sending'), false, Config::get('feature_lock', 'edit_posts', false)),
				array('commtag',	t('Tagging'),				t('Ability to tag existing posts'), false, Config::get('feature_lock', 'commtag', false)),
				array('categories',	t('Post Categories'),			t('Add categories to your posts'), false, Config::get('feature_lock', 'categories', false)),
				array('filing',		t('Saved Folders'),			t('Ability to file posts under folders'), false, Config::get('feature_lock', 'filing', false)),
				array('dislike',	t('Dislike Posts'),			t('Ability to dislike posts/comments'), false, Config::get('feature_lock', 'dislike', false)),
				array('star_posts',	t('Star Posts'),			t('Ability to mark special posts with a star indicator'), false, Config::get('feature_lock', 'star_posts', false)),
				array('ignore_posts',	t('Mute Post Notifications'),		t('Ability to mute notifications for a thread'), false, Config::get('feature_lock', 'ignore_posts', false)),
			),

			// Advanced Profile Settings
			'advanced_profile' => array(
				t('Advanced Profile Settings'),
				array('forumlist_profile', t('List Forums'),			t('Show visitors public community forums at the Advanced Profile Page'), false, Config::get('feature_lock', 'forumlist_profile', false)),
				array('tagadelic',	t('Tag Cloud'),				t('Provide a personal tag cloud on your profile page'), false, Config::get('feature_lock', 'tagadelic', false)),
			),
		);

		// removed any locked features and remove the entire category if this makes it empty

		if ($filtered) {
			foreach ($arr as $k => $x) {
				$has_items = false;
				$kquantity = count($arr[$k]);
				for ($y = 0; $y < $kquantity; $y ++) {
					if (is_array($arr[$k][$y])) {
						if ($arr[$k][$y][4] === false) {
							$has_items = true;
						} else {
							unset($arr[$k][$y]);
						}
					}
				}
				if (! $has_items) {
					unset($arr[$k]);
				}
			}
		}

		call_hooks('get', $arr);
		return $arr;
	}
}
