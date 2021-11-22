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

namespace Friendica\Factory\Api\Twitter;

use Friendica\BaseFactory;
use Friendica\Content\Text\BBCode;
use Friendica\Database\Database;
use Friendica\Factory\Api\Twitter\User as TwitterUser;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use ImagickException;
use Psr\Log\LoggerInterface;

class Status extends BaseFactory
{
	/** @var Database */
	private $dba;
	/** @var TwitterUser */
	private $twitterUser;

	public function __construct(LoggerInterface $logger, Database $dba, TwitterUser $twitteruser)
	{
		parent::__construct($logger);
		$this->dba         = $dba;
		$this->twitterUser = $twitteruser;
	}

	/**
	 * @param int $uriId Uri-ID of the item
	 * @param int $uid   Item user
	 *
	 * @return \Friendica\Object\Api\Mastodon\Status
	 * @throws HTTPException\InternalServerErrorException
	 * @throws ImagickException|HTTPException\NotFoundException
	 */
	public function createFromUriId(int $uriId, $uid = 0): \Friendica\Object\Api\Twitter\Status
	{
		$fields = ['id', 'parent', 'uri-id', 'uid', 'author-id', 'author-link', 'author-network', 'owner-id', 'starred', 'app', 'title', 'body', 'raw-body', 'created', 'network',
			'thr-parent-id', 'parent-author-id', 'parent-author-nick', 'language', 'uri', 'plink', 'private', 'vid', 'gravity'];
		$item = Post::selectFirst($fields, ['uri-id' => $uriId, 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (!$item) {
			throw new HTTPException\NotFoundException('Item with URI ID ' . $uriId . ' not found' . ($uid ? ' for user ' . $uid : '.'));
		}

		$author = $this->twitterUser->createFromContactId($item['author-id'], $item['uid']);
		$owner  = $this->twitterUser->createFromContactId($item['owner-id'], $item['uid']);

		$friendica_comments = Post::countPosts(['thr-parent-id' => $item['uri-id'], 'deleted' => false, 'gravity' => GRAVITY_COMMENT]);

		$geo = [];

		//$mentions    = $this->mstdnMentionFactory->createFromUriId($uriId)->getArrayCopy();
		//$tags        = $this->mstdnTagFactory->createFromUriId($uriId);
		//$attachments = $this->mstdnAttachementFactory->createFromUriId($uriId);
		$entities             = [];
		$attachments          = [];
		$friendica_activities = [];

		$shared = BBCode::fetchShareAttributes($item['body']);
		if (!empty($shared['guid'])) {
			//$shared_item = Post::selectFirst(['uri-id', 'plink'], ['guid' => $shared['guid']]);

			//$shared_uri_id = $shared_item['uri-id'] ?? 0;

			//$mentions    = array_merge($mentions, $this->mstdnMentionFactory->createFromUriId($shared_uri_id)->getArrayCopy());
			//$tags        = array_merge($tags, $this->mstdnTagFactory->createFromUriId($shared_uri_id));
			//$attachments = array_merge($attachments, $this->mstdnAttachementFactory->createFromUriId($shared_uri_id));
			$entities             = [];
			$attachments          = [];
			$friendica_activities = [];
		}

		if ($item['vid'] == Verb::getID(Activity::ANNOUNCE)) {
			$retweeted      = $this->createFromUriId($item['thr-parent-id'], $uid)->toArray();
			$retweeted_item = Post::selectFirst(['title', 'body', 'author-id'], ['uri-id' => $item['thr-parent-id'],'uid' => [0, $uid]]);
			$item['title']  = $retweeted_item['title'] ?? $item['title'];
			$item['body']   = $retweeted_item['body'] ?? $item['body'];
			$author         = $this->twitterUser->createFromContactId($retweeted_item['author-id'], $item['uid']);
		} else {
			$retweeted = [];
		}

		$quoted = [];
	
		return new \Friendica\Object\Api\Twitter\Status($item, $author, $owner, $retweeted, $quoted, $attachments, $geo, $friendica_activities, $entities, $friendica_comments);
	}
}
