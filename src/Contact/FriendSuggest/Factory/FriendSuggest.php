<?php

namespace Friendica\Contact\FriendSuggest\Factory;

use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Contact\FriendSuggest\Entity;

class FriendSuggest extends BaseFactory implements ICanCreateFromTableRow
{
	/**
	 * @inheritDoc
	 */
	public function createFromTableRow(array $row): Entity\FriendSuggest
	{
		return new Entity\FriendSuggest(
			$row['uid'] ?? 0,
			$row['cid'] ?? 0,
			$row['name'] ?? '',
			$row['url'] ?? '',
			$row['request'] ?? '',
			$row['photo'] ?? '',
			$row['note'] ?? '',
			new \DateTime($row['created'] ?? 'now', new \DateTimeZone('UTC')),
			$row['id'] ?? null
		);
	}

	public function createNew(
		int $uid,
		int $cid,
		string $name = '',
		string $url = '',
		string $request = '',
		string $photo = '',
		string $note = ''
	): Entity\FriendSuggest
	{
		return $this->createFromTableRow([
			'uid'     => $uid,
			'cid'     => $cid,
			'name'    => $name,
			'url'     => $url,
			'request' => $request,
			'photo'   => $photo,
			'note'    => $note,
		]);
	}
}
