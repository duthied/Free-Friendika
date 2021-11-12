<?php

namespace Friendica\Test\src\Module\Api\Friendica\Photo;

use Friendica\Module\Api\Friendica\Photoalbum\Delete;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\src\Module\Api\ApiTest;

class DeleteTest extends ApiTest
{
	public function testEmpty()
	{
		self::expectException(BadRequestException::class);
		Delete::rawContent();
	}

	public function testWrong()
	{
		self::expectException(BadRequestException::class);
		Delete::rawContent(['album' => 'album_name']);
	}

	public function testValid()
	{
		self::markTestIncomplete('We need to add a dataset for this.');
	}
}
