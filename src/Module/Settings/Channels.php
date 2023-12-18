<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

use Friendica\App;
use Friendica\Content\Conversation\Factory;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Circle;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Channels extends BaseSettings
{
	/** @var UserDefinedChannel */
	private $channel;
	/** @var Factory\UserDefinedChannel */
	private $userDefinedChannel;

	public function __construct(Factory\UserDefinedChannel $userDefinedChannel, UserDefinedChannel $channel, App\Page $page, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->userDefinedChannel = $userDefinedChannel;
		$this->channel            = $channel;
	}

	protected function post(array $request = [])
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		if (empty($request['edit_channel']) && empty($request['add_channel'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/settings/channels', 'settings_channels');

		if (!empty($request['add_channel'])) {
			$channel = $this->userDefinedChannel->createFromTableRow([
				'label'            => $request['new_label'],
				'description'      => $request['new_description'],
				'access-key'       => substr(mb_strtolower($request['new_access_key']), 0, 1),
				'uid'              => $uid,
				'circle'           => (int)$request['new_circle'],
				'include-tags'     => $this->cleanTags($request['new_include_tags']),
				'exclude-tags'     => $this->cleanTags($request['new_exclude_tags']),
				'full-text-search' => $request['new_text_search'],
				'media-type'       => ($request['new_image'] ? 1 : 0) | ($request['new_video'] ? 2 : 0) | ($request['new_audio'] ? 4 : 0),
			]);
			$saved = $this->channel->save($channel);
			$this->logger->debug('New channel added', ['saved' => $saved]);
			return;
		}

		foreach (array_keys((array)$request['label']) as $id) {
			if ($request['delete'][$id]) {
				$success = $this->channel->deleteById($id, $uid);
				$this->logger->debug('Channel deleted', ['id' => $id, 'success' => $success]);
				continue;
			}

			$channel = $this->userDefinedChannel->createFromTableRow([
				'id'               => $id,
				'label'            => $request['label'][$id],
				'description'      => $request['description'][$id],
				'access-key'       => substr(mb_strtolower($request['access_key'][$id]), 0, 1),
				'uid'              => $uid,
				'circle'           => (int)$request['circle'][$id],
				'include-tags'     => $this->cleanTags($request['include_tags'][$id]),
				'exclude-tags'     => $this->cleanTags($request['exclude_tags'][$id]),
				'full-text-search' => $request['text_search'][$id],
				'media-type'       => ($request['image'][$id] ? 1 : 0) | ($request['video'][$id] ? 2 : 0) | ($request['audio'][$id] ? 4 : 0),
			]);
			$saved = $this->channel->save($channel);
			$this->logger->debug('Save channel', ['id' => $id, 'saved' => $saved]);
		}
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		$circles = [
			0  => $this->l10n->t('Global Community'),
			-3 => $this->l10n->t('Network'),
			-1 => $this->l10n->t('Following'),
			-2 => $this->l10n->t('Followers'),
		];

		foreach (Circle::getByUserId($uid) as $circle) {
			$circles[$circle['id']] = $circle['name'];
		}

		$channels = [];
		foreach ($this->channel->selectByUid($uid) as $channel) {
			if (!empty($request['id'])) {
				$open = $channel->code == $request['id'];
			} elseif (!empty($request['new_label'])) {
				$open = $channel->label == $request['new_label'];
			} else {
				$open = false;
			}

			$channels[] = [
				'id'           => $channel->code,
				'open'         => $open,
				'label'        => ["label[$channel->code]", $this->t('Label'), $channel->label, '', $this->t('Required')],
				'description'  => ["description[$channel->code]", $this->t("Description"), $channel->description],
				'access_key'   => ["access_key[$channel->code]", $this->t("Access Key"), $channel->accessKey],
				'circle'       => ["circle[$channel->code]", $this->t('Circle/Channel'), $channel->circle, '', $circles],
				'include_tags' => ["include_tags[$channel->code]", $this->t("Include Tags"), str_replace(',', ', ', $channel->includeTags)],
				'exclude_tags' => ["exclude_tags[$channel->code]", $this->t("Exclude Tags"), str_replace(',', ', ', $channel->excludeTags)],
				'text_search'  => ["text_search[$channel->code]", $this->t("Full Text Search"), $channel->fullTextSearch],
				'image'        => ["image[$channel->code]", $this->t("Images"), $channel->mediaType & 1],
				'video'        => ["video[$channel->code]", $this->t("Videos"), $channel->mediaType & 2],
				'audio'        => ["audio[$channel->code]", $this->t("Audio"), $channel->mediaType & 4],
				'delete'       => ["delete[$channel->code]", $this->t("Delete channel") . ' (' . $channel->label . ')', false, $this->t("Check to delete this entry from the channel list")]
			];
		}

		$t = Renderer::getMarkupTemplate('settings/channels.tpl');
		return Renderer::replaceMacros($t, [
			'open'         => count($channels) == 0,
			'label'        => ["new_label", $this->t('Label'), '', $this->t('Short name for the channel. It is displayed on the channels widget.'), $this->t('Required')],
			'description'  => ["new_description", $this->t("Description"), '', $this->t('This should describe the content of the channel in a few word.')],
			'access_key'   => ["new_access_key", $this->t("Access Key"), '', $this->t('When you want to access this channel via an access key, you can define it here. Pay attention to not use an already used one.')],
			'circle'       => ['new_circle', $this->t('Circle/Channel'), 0, $this->t('Select a circle or channel, that your channel should be based on.'), $circles],
			'include_tags' => ["new_include_tags", $this->t("Include Tags"), '', $this->t('Comma separated list of tags. A post will be used when it contains any of the listed tags.')],
			'exclude_tags' => ["new_exclude_tags", $this->t("Exclude Tags"), '', $this->t('Comma separated list of tags. If a post contain any of these tags, then it will not be part of nthis channel.')],
			'text_search'  => ["new_text_search", $this->t("Full Text Search"), '', $this->t('Search terms for the body, supports the "boolean mode" operators from MariaDB. See the help for a complete list of operators and additional keywords: %s', '<a href="help/Channels">help/Channels</a>')],
			'image'        => ['new_image', $this->t("Images"), false, $this->t("Check to display images in the channel.")],
			'video'        => ["new_video", $this->t("Videos"), false, $this->t("Check to display videos in the channel.")],
			'audio'        => ["new_audio", $this->t("Audio"), false, $this->t("Check to display audio in the channel.")],
			'$l10n'        => [
				'title'          => $this->t('Channels'),
				'intro'          => $this->t('This page can be used to define your own channels.'),
				'addtitle'       => $this->t('Add new entry to the channel list'),
				'addsubmit'      => $this->t('Add'),
				'savechanges'    => $this->t('Save'),
				'currenttitle'   => $this->t('Current Entries in the channel list'),
				'thurl'          => $this->t('Blocked server domain pattern'),
				'threason'       => $this->t('Reason for the block'),
				'delentry'       => $this->t('Delete entry from the channel list'),
				'confirm_delete' => $this->t('Delete entry from the channel list?'),
			],
			'$entries' => $channels,
			'$baseurl' => $this->baseUrl,

			'$form_security_token' => self::getFormSecurityToken('settings_channels'),
		]);
	}

	private function cleanTags(string $tag_list): string
	{
		$tags = [];

		$tagitems = explode(',', mb_strtolower($tag_list));
		foreach ($tagitems as $tag) {
			$tag = trim($tag, '# ');
			if (!empty($tag)) {
				$tags[] = preg_replace('#\s#u', '', $tag);
			}
		}
		return implode(',', $tags);
	}
}
