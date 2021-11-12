<?php

namespace Friendica\Test\src\Module\Api\Friendica\Photo;

use Friendica\Module\Api\Friendica\Photo\Delete;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\src\Module\Api\ApiTest;

class DeleteTest extends ApiTest
{
	public function testEmpty()
	{
		$this->expectException(BadRequestException::class);
		Delete::rawContent();
	}

	public function testWithoutAuthenticatedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');
	}

	public function testWrong()
	{
		$this->expectException(BadRequestException::class);
		Delete::rawContent(['photo_id' => 1]);
	}

	public function testWithCorrectPhotoId()
	{
		self::markTestIncomplete('We need to add a dataset for this.');
	}
}
