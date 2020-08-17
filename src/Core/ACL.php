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

namespace Friendica\Core;

use Friendica\App\Page;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;

/**
 * Handle ACL management and display
 */
class ACL
{
	/**
	 * Returns a select input tag with all the contact of the local user
	 *
	 * @param string $selname     Name attribute of the select input tag
	 * @param string $selclass    Class attribute of the select input tag
	 * @param array  $preselected Contact IDs that should be already selected
	 * @param int    $size        Length of the select box
	 * @param int    $tabindex    Select input tag tabindex attribute
	 * @return string
	 * @throws \Exception
	 */
	public static function getMessageContactSelectHTML($selname, $selclass, array $preselected = [], $size = 4, $tabindex = null)
	{
		$a = DI::app();

		$o = '';

		// When used for private messages, we limit correspondence to mutual DFRN/Friendica friends and the selector
		// to one recipient. By default our selector allows multiple selects amongst all contacts.
		$sql_extra = sprintf(" AND `rel` = %d ", intval(Contact::FRIEND));
		$sql_extra .= sprintf(" AND `network` IN ('%s' , '%s') ", Protocol::DFRN, Protocol::DIASPORA);

		$tabindex_attr = !empty($tabindex) ? ' tabindex="' . intval($tabindex) . '"' : '';

		$hidepreselected = '';
		if ($preselected) {
			$sql_extra .= " AND `id` IN (" . implode(",", $preselected) . ")";
			$hidepreselected = ' style="display: none;"';
		}

		$o .= "<select name=\"$selname\" id=\"$selclass\" class=\"$selclass\" size=\"$size\"$tabindex_attr$hidepreselected>\r\n";

		$stmt = DBA::p("SELECT `id`, `name`, `url`, `network` FROM `contact`
			WHERE `uid` = ? AND NOT `self` AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND NOT `deleted` AND `notify` != ''
			$sql_extra
			ORDER BY `name` ASC ", intval(local_user())
		);

		$contacts = DBA::toArray($stmt);

		$arr = ['contact' => $contacts, 'entry' => $o];

		// e.g. 'network_pre_contact_deny', 'profile_pre_contact_allow'
		Hook::callAll(DI::module()->getName() . '_pre_' . $selname, $arr);

		$receiverlist = [];

		if (DBA::isResult($contacts)) {
			foreach ($contacts as $contact) {
				if (in_array($contact['id'], $preselected)) {
					$selected = ' selected="selected"';
				} else {
					$selected = '';
				}

				$trimmed = Protocol::formatMention($contact['url'], $contact['name']);

				$receiverlist[] = $trimmed;

				$o .= "<option value=\"{$contact['id']}\"$selected title=\"{$contact['name']}|{$contact['url']}\" >$trimmed</option>\r\n";
			}
		}

		$o .= '</select>' . PHP_EOL;

		if ($preselected) {
			$o .= implode(', ', $receiverlist);
		}

		Hook::callAll(DI::module()->getName() . '_post_' . $selname, $o);

		return $o;
	}

	/**
	 * Return the default permission of the provided user array
	 *
	 * @param array $user
	 * @return array Hash of contact id lists
	 * @throws \Exception
	 */
	public static function getDefaultUserPermissions(array $user = null)
	{
		$aclFormatter = DI::aclFormatter();

		return [
			'allow_cid' => Contact::pruneUnavailable($aclFormatter->expand($user['allow_cid'] ?? '')),
			'allow_gid' => $aclFormatter->expand($user['allow_gid'] ?? ''),
			'deny_cid'  => $aclFormatter->expand($user['deny_cid']  ?? ''),
			'deny_gid'  => $aclFormatter->expand($user['deny_gid']  ?? ''),
		];
	}

	/**
	 * Returns the ACL list of contacts for a given user id
	 *
	 * @param int   $user_id
	 * @param array $condition Additional contact lookup table conditions
	 * @return array
	 * @throws \Exception
	 */
	public static function getContactListByUserId(int $user_id, array $condition = [])
	{
		$fields = ['id', 'name', 'addr', 'micro'];
		$params = ['order' => ['name']];
		$acl_contacts = Contact::selectToArray(
			$fields,
			array_merge([
				'uid' => $user_id,
				'self' => false,
				'blocked' => false,
				'archive' => false,
				'deleted' => false,
				'pending' => false,
				'rel' => [Contact::FOLLOWER, Contact::FRIEND]
			], $condition),
			$params
		);

		$acl_yourself = Contact::selectFirst($fields, ['uid' => $user_id, 'self' => true]);
		$acl_yourself['name'] = DI::l10n()->t('Yourself');

		$acl_contacts[] = $acl_yourself;

		$acl_forums = Contact::selectToArray($fields,
			['uid' => $user_id, 'self' => false, 'blocked' => false, 'archive' => false, 'deleted' => false,
			'pending' => false, 'contact-type' => Contact::TYPE_COMMUNITY], $params
		);

		$acl_contacts = array_merge($acl_forums, $acl_contacts);

		array_walk($acl_contacts, function (&$value) {
			$value['type'] = 'contact';
		});

		return $acl_contacts;
	}

	/**
	 * Returns the ACL list of groups (including meta-groups) for a given user id
	 *
	 * @param int $user_id
	 * @return array
	 */
	public static function getGroupListByUserId(int $user_id)
	{
		$acl_groups = [
			[
				'id' => Group::FOLLOWERS,
				'name' => DI::l10n()->t('Followers'),
				'addr' => '',
				'micro' => 'images/twopeople.png',
				'type' => 'group',
			],
			[
				'id' => Group::MUTUALS,
				'name' => DI::l10n()->t('Mutuals'),
				'addr' => '',
				'micro' => 'images/twopeople.png',
				'type' => 'group',
			]
		];
		foreach (Group::getByUserId($user_id) as $group) {
			$acl_groups[] = [
				'id' => $group['id'],
				'name' => $group['name'],
				'addr' => '',
				'micro' => 'images/twopeople.png',
				'type' => 'group',
			];
		}

		return $acl_groups;
	}

	/**
	 * Return the full jot ACL selector HTML
	 *
	 * @param Page   $page
	 * @param array  $user                  User array
	 * @param bool   $for_federation
	 * @param array  $default_permissions   Static defaults permission array:
	 *                                      [
	 *                                      'allow_cid' => [],
	 *                                      'allow_gid' => [],
	 *                                      'deny_cid' => [],
	 *                                      'deny_gid' => []
	 *                                      ]
	 * @param array  $condition
	 * @param string $form_prefix
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getFullSelectorHTML(
		Page $page,
		array $user = null,
		bool $for_federation = false,
		array $default_permissions = [],
		array $condition = [],
		$form_prefix = ''
	) {
		if (empty($user['uid'])) {
			return '';
		}

		static $input_group_id = 0;

		$input_group_id++;

		$page->registerFooterScript(Theme::getPathForFile('asset/typeahead.js/dist/typeahead.bundle.js'));
		$page->registerFooterScript(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.js'));
		$page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.css'));
		$page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput-typeahead.css'));

		// Defaults user permissions
		if (empty($default_permissions)) {
			$default_permissions = self::getDefaultUserPermissions($user);
		}

		$default_permissions = [
			'allow_cid' => $default_permissions['allow_cid'] ?? [],
			'allow_gid' => $default_permissions['allow_gid'] ?? [],
			'deny_cid'  => $default_permissions['deny_cid']  ?? [],
			'deny_gid'  => $default_permissions['deny_gid']  ?? [],
		];

		if (count($default_permissions['allow_cid'])
			+ count($default_permissions['allow_gid'])
			+ count($default_permissions['deny_cid'])
			+ count($default_permissions['deny_gid'])) {
			$visibility = 'custom';
		} else {
			$visibility = 'public';
			// Default permission display for custom panel
			$default_permissions['allow_gid'] = [Group::FOLLOWERS];
		}

		$jotnets_fields = [];
		if ($for_federation) {
			if (function_exists('imap_open') && !DI::config()->get('system', 'imap_disabled')) {
				$mailacct = DBA::selectFirst('mailacct', ['pubmail'], ['`uid` = ? AND `server` != ""', $user['uid']]);
				if (DBA::isResult($mailacct)) {
					$jotnets_fields[] = [
						'type' => 'checkbox',
						'field' => [
							'pubmail_enable',
							DI::l10n()->t('Post to Email'),
							!empty($mailacct['pubmail'])
						]
					];
	
				}
			}
			Hook::callAll('jot_networks', $jotnets_fields);
		}

		$acl_contacts = self::getContactListByUserId($user['uid'], $condition);

		$acl_groups = self::getGroupListByUserId($user['uid']);

		$acl_list = array_merge($acl_groups, $acl_contacts);

		$input_names = [
			'visibility'    => $form_prefix ? $form_prefix . '[visibility]'    : 'visibility',
			'group_allow'   => $form_prefix ? $form_prefix . '[group_allow]'   : 'group_allow',
			'contact_allow' => $form_prefix ? $form_prefix . '[contact_allow]' : 'contact_allow',
			'group_deny'    => $form_prefix ? $form_prefix . '[group_deny]'    : 'group_deny',
			'contact_deny'  => $form_prefix ? $form_prefix . '[contact_deny]'  : 'contact_deny',
			'emailcc'       => $form_prefix ? $form_prefix . '[emailcc]'       : 'emailcc',
		];

		$tpl = Renderer::getMarkupTemplate('acl_selector.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$public_title'   => DI::l10n()->t('Public'),
			'$public_desc'    => DI::l10n()->t('This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'),
			'$custom_title'   => DI::l10n()->t('Limited/Private'),
			'$custom_desc'    => DI::l10n()->t('This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.'),
			'$allow_label'    => DI::l10n()->t('Show to:'),
			'$deny_label'     => DI::l10n()->t('Except to:'),
			'$emailcc'        => DI::l10n()->t('CC: email addresses'),
			'$emtitle'        => DI::l10n()->t('Example: bob@example.com, mary@example.com'),
			'$jotnets_summary' => DI::l10n()->t('Connectors'),
			'$visibility'     => $visibility,
			'$acl_contacts'   => $acl_contacts,
			'$acl_groups'     => $acl_groups,
			'$acl_list'       => $acl_list,
			'$contact_allow'  => implode(',', $default_permissions['allow_cid']),
			'$group_allow'    => implode(',', $default_permissions['allow_gid']),
			'$contact_deny'   => implode(',', $default_permissions['deny_cid']),
			'$group_deny'     => implode(',', $default_permissions['deny_gid']),
			'$for_federation' => $for_federation,
			'$jotnets_fields' => $jotnets_fields,
			'$input_names'    => $input_names,
			'$input_group_id' => $input_group_id,
		]);

		return $o;
	}
}
