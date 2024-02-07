<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Circle;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Network\HTTPException;

/**
 * Outputs the permission tooltip HTML content for the provided item, photo or event id.
 */
class PermissionTooltip extends \Friendica\BaseModule
{
	protected function rawContent(array $request = [])
	{
		$type = $this->parameters['type'];
		$referenceId = $this->parameters['id'];

		$expectedTypes = ['item', 'photo', 'event'];
		if (!in_array($type, $expectedTypes)) {
			throw new HTTPException\BadRequestException(DI::l10n()->t('Wrong type "%s", expected one of: %s', $type, implode(', ', $expectedTypes)));
		}

		$condition = ['id' => $referenceId, 'uid' => [0, DI::userSession()->getLocalUserId()]];
		if ($type == 'item') {
			$fields = ['uid', 'psid', 'private', 'uri-id', 'origin', 'network'];
			$model = Post::selectFirst($fields, $condition, ['order' => ['uid' => true]]);

			if ($model['origin'] || ($model['network'] != Protocol::ACTIVITYPUB)) {
				$permissionSet = DI::permissionSet()->selectOneById($model['psid'], $model['uid']);
				$model['allow_cid'] = $permissionSet->allow_cid;
				$model['allow_gid'] = $permissionSet->allow_gid;
				$model['deny_cid']  = $permissionSet->deny_cid;
				$model['deny_gid']  = $permissionSet->deny_gid;
			} else {
				$model['allow_cid'] = [];
				$model['allow_gid'] = [];
				$model['deny_cid']  = [];
				$model['deny_gid']  = [];
			}
		} else {
			$fields = ['uid', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid'];
			$model = DBA::selectFirst($type, $fields, $condition);
			$model['allow_cid'] = DI::aclFormatter()->expand($model['allow_cid']);
			$model['allow_gid'] = DI::aclFormatter()->expand($model['allow_gid']);
			$model['deny_cid']  = DI::aclFormatter()->expand($model['deny_cid']);
			$model['deny_gid']  = DI::aclFormatter()->expand($model['deny_gid']);
		}

		if (!DBA::isResult($model)) {
			throw new HttpException\NotFoundException(DI::l10n()->t('Model not found'));
		}

		// Kept for backwards compatibility
		Hook::callAll('lockview_content', $model);

		if ($type == 'item') {
			$receivers = $this->fetchReceivers($model['uri-id']);
			if (empty($receivers)) {
				switch ($model['private']) {
					case Item::PUBLIC:
						$receivers = DI::l10n()->t('Public');
						break;

					case Item::UNLISTED:
						$receivers = DI::l10n()->t('Unlisted');
						break;

					case Item::PRIVATE:
						$receivers = DI::l10n()->t('Limited/Private');
						break;
				}
			}
		} else {
			$receivers = '';
		}

		if (empty($model['allow_cid'])
			&& empty($model['allow_gid'])
			&& empty($model['deny_cid'])
			&& empty($model['deny_gid'])
			&& empty($receivers))
		{
			echo DI::l10n()->t('Remote privacy information not available.');
			exit;
		}

		if (!empty($model['allow_cid']) || !empty($model['allow_gid']) || !empty($model['deny_cid']) || !empty($model['deny_gid'])) {
			$receivers = $this->fetchReceiversFromACL($model);
		}

		$this->httpExit(DI::l10n()->t('Visible to:') . '<br />' . $receivers);
	}

	/**
	 * Fetch a list of receivers based on the ACL data
	 *
	 * @param array $model
	 * @return string
	 */
	private function fetchReceiversFromACL(array $model)
	{
		$allowed_users   = $model['allow_cid'];
		$allowed_circles = $model['allow_gid'];
		$deny_users      = $model['deny_cid'];
		$deny_circles    = $model['deny_gid'];

		$l = [];

		if (count($allowed_circles)) {
			$key = array_search(Circle::FOLLOWERS, $allowed_circles);
			if ($key !== false) {
				$l[] = '<b>' . DI::l10n()->t('Followers') . '</b>';
				unset($allowed_circles[$key]);
			}

			$key = array_search(Circle::MUTUALS, $allowed_circles);
			if ($key !== false) {
				$l[] = '<b>' . DI::l10n()->t('Mutuals') . '</b>';
				unset($allowed_circles[$key]);
			}

			foreach (DI::dba()->selectToArray('group', ['name'], ['id' => $allowed_circles]) as $circle) {
				$l[] = '<b>' . $circle['name'] . '</b>';
			}
		}

		foreach (DI::dba()->selectToArray('contact', ['name'], ['id' => $allowed_users]) as $contact) {
			$l[] = $contact['name'];
		}

		if (count($deny_circles)) {
			$key = array_search(Circle::FOLLOWERS, $deny_circles);
			if ($key !== false) {
				$l[] = '<b><strike>' . DI::l10n()->t('Followers') . '</strike></b>';
				unset($deny_circles[$key]);
			}

			$key = array_search(Circle::MUTUALS, $deny_circles);
			if ($key !== false) {
				$l[] = '<b><strike>' . DI::l10n()->t('Mutuals') . '</strike></b>';
				unset($deny_circles[$key]);
			}

			foreach (DI::dba()->selectToArray('group', ['name'], ['id' => $allowed_circles]) as $circle) {
				$l[] = '<b><strike>' . $circle['name'] . '</strike></b>';
			}
		}

		foreach (DI::dba()->selectToArray('contact', ['name'], ['id' => $deny_users]) as $contact) {
			$l[] = '<strike>' . $contact['name'] . '</strike>';
		}

		return implode(', ', $l);
	}

	/**
	 * Fetch a list of receivers
	 *
	 * @param int $uriId
	 * @return string
	 */
	private function fetchReceivers(int $uriId): string
	{
		$own_url = '';
		$uid = DI::userSession()->getLocalUserId();
		if ($uid) {
			$owner = User::getOwnerDataById($uid);
			if (!empty($owner['url'])) {
				$own_url = $owner['url'];
			}
		}

		$receivers = [];
		foreach (Tag::getByURIId($uriId, [Tag::TO, Tag::CC, Tag::BCC, Tag::AUDIENCE, Tag::ATTRIBUTED]) as $receiver) {
			// We only display BCC when it contains the current user
			if (($receiver['type'] == Tag::BCC) && ($receiver['url'] != $own_url)) {
				continue;
			}

			switch (Tag::getTargetType($receiver['url'], false)) {
				case Tag::PUBLIC_COLLECTION:
					$receivers[$receiver['type']][] = DI::l10n()->t('Public');
					break;
				case Tag::GENERAL_COLLECTION:
					$receivers[$receiver['type']][] = DI::l10n()->t('Collection (%s)', $receiver['name']);
					break;
				case Tag::FOLLOWER_COLLECTION:
					$apcontact = DBA::selectFirst('apcontact', ['name'], ['followers' => $receiver['url']]);
					$receivers[$receiver['type']][] = DI::l10n()->t('Followers (%s)', $apcontact['name'] ?? $receiver['name']);
					break;
				case Tag::ACCOUNT:
					$apcontact = APContact::getByURL($receiver['url'], false);
					$receivers[$receiver['type']][] = $apcontact['name'] ?? $receiver['name'];
					break;
				default:
					$receivers[$receiver['type']][] = $receiver['name'];
					break;
			}
		}

		$output = '';

		foreach ($receivers as $type => $receiver) {
			$max = DI::config()->get('system', 'max_receivers');
			$total = count($receiver);
			if ($total > $max) {
				$receiver = array_slice($receiver, 0, $max);
				$receiver[] = DI::l10n()->t('%d more', $total - $max);
			}
			switch ($type) {
				case Tag::TO:
					$output .= DI::l10n()->t('<b>To:</b> %s<br>', implode(', ', $receiver));
					break;
				case Tag::CC:
					$output .= DI::l10n()->t('<b>CC:</b> %s<br>', implode(', ', $receiver));
					break;
				case Tag::BCC:
					$output .= DI::l10n()->t('<b>BCC:</b> %s<br>', implode(', ', $receiver));
					break;
				case Tag::AUDIENCE:
					$output .= DI::l10n()->t('<b>Audience:</b> %s<br>', implode(', ', $receiver));
					break;
				case Tag::ATTRIBUTED:
					$output .= DI::l10n()->t('<b>Attributed To:</b> %s<br>', implode(', ', $receiver));
					break;
			}
		}

		return $output;
	}
}
