<?php

namespace Friendica\Test\src\Module\Api\Friendica\Photoalbum;

use Friendica\Module\Api\Friendica\Photoalbum\Update;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\src\Module\Api\ApiTest;

class UpdateTest extends ApiTest
{
	public function testEmpty()
	{
		$this->expectException(BadRequestException::class);
		Update::rawContent();
	}

	public function testTooFewArgs()
	{
		$this->expectException(BadRequestException::class);
		Update::rawContent(['album' => 'album_name']);
	}

	public function testWrongUpdate()
	{
		$this->expectException(BadRequestException::class);
		Update::rawContent(['album' => 'album_name', 'album_new' => 'album_name']);
	}

	public function testWithoutAuthenticatedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');
	}

	public function testValid()
	{
		self::markTestIncomplete('We need to add a dataset for this.');
	}
}
