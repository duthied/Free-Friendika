<?php

namespace Friendica\Test\src\Contact\FriendSuggest\Factory;

use Friendica\Contact\FriendSuggest\Factory\FriendSuggest;
use Friendica\Contact\FriendSuggest\Entity;
use Friendica\Test\MockedTest;
use Friendica\Util\Logger\VoidLogger;

class FriendSuggestTest extends MockedTest
{
	public function dataCreate()
	{
		return [
			'default' => [
				'input' => [
					'uid'     => 12,
					'cid'     => 13,
					'name'    => 'test',
					'url'     => 'https://friendica.local/profile/test',
					'request' => 'https://friendica.local/dfrn_request/test',
					'photo'   => 'https://friendica.local/photo/profile/test',
					'note'    => 'a common note',
					'created' => '2021-10-12 12:23:00'
				],
				'assertion' => [
					'uid'     => 12,
					'cid'     => 13,
					'name'    => 'test',
					'url'     => 'https://friendica.local/profile/test',
					'request' => 'https://friendica.local/dfrn_request/test',
					'photo'   => 'https://friendica.local/photo/profile/test',
					'note'    => 'a common note',
					'created' => new \DateTime('2021-10-12 12:23:00', new \DateTimeZone('UTC')),
					'id'      => null,
				],
			],
			'minimum' => [
				'input' => [
					'id' => 20,
				],
				'assertion' => [
					'id' => 20,
				]
			],
			'full' => [
				'input' => [
					'uid'     => 12,
					'cid'     => 13,
					'name'    => 'test',
					'url'     => 'https://friendica.local/profile/test',
					'request' => 'https://friendica.local/dfrn_request/test',
					'photo'   => 'https://friendica.local/photo/profile/test',
					'note'    => 'a common note',
					'created' => '2021-10-12 12:23:00',
					'id'      => 666,
				],
				'assertion' => [
					'uid'     => 12,
					'cid'     => 13,
					'name'    => 'test',
					'url'     => 'https://friendica.local/profile/test',
					'request' => 'https://friendica.local/dfrn_request/test',
					'photo'   => 'https://friendica.local/photo/profile/test',
					'note'    => 'a common note',
					'created' => new \DateTime('2021-10-12 12:23:00', new \DateTimeZone('UTC')),
					'id'      => 666,
				],
			],
		];
	}

	public function assertFriendSuggest(Entity\FriendSuggest $friendSuggest, array $assertion)
	{
		self::assertEquals($assertion['id'] ?? null, $friendSuggest->id);
		self::assertEquals($assertion['uid'] ?? 0, $friendSuggest->uid);
		self::assertEquals($assertion['cid'] ?? 0, $friendSuggest->cid);
		self::assertEquals($assertion['name'] ?? '', $friendSuggest->name);
		self::assertEquals($assertion['url'] ?? '', $friendSuggest->url);
		self::assertEquals($assertion['request'] ?? '', $friendSuggest->request);
		self::assertEquals($assertion['photo'] ?? '', $friendSuggest->photo);
		self::assertEquals($assertion['note'] ?? '', $friendSuggest->note);
		if (empty($assertion['created'])) {
			self::assertInstanceOf(\DateTime::class, $friendSuggest->created);
		} else {
			self::assertEquals($assertion['created'], $friendSuggest->created);
		}
	}

	public function testCreateNew()
	{
		$factory = new FriendSuggest(new VoidLogger());

		$this->assertFriendSuggest($factory->createNew(12, 13), ['uid' => 12, 'cid' => 13]);
	}

	/**
	 * @dataProvider dataCreate
	 */
	public function testCreateFromTableRow(array $input, array $assertion)
	{
		$factory = new FriendSuggest(new VoidLogger());

		$this->assertFriendSuggest($factory->createFromTableRow($input), $assertion);
	}

	public function testCreateEmpty()
	{
		$factory = new FriendSuggest(new VoidLogger());

		$this->assertFriendSuggest($factory->createEmpty(66), ['id' => 66]);
	}
}
