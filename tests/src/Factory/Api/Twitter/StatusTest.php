<?php

namespace Friendica\Test\src\Factory\Api\Twitter;

use Friendica\DI;
use Friendica\Factory\Api\Friendica\Activities;
use Friendica\Factory\Api\Twitter\Attachment;
use Friendica\Factory\Api\Twitter\Hashtag;
use Friendica\Factory\Api\Twitter\Media;
use Friendica\Factory\Api\Twitter\Mention;
use Friendica\Factory\Api\Twitter\Status;
use Friendica\Factory\Api\Twitter\Url;
use Friendica\Test\FixtureTest;
use Friendica\Test\src\Module\Api\ApiTest;

class StatusTest extends FixtureTest
{
	/**
	 * Test the api_convert_item() function.
	 *
	 * @return void
	 */
	public function testApiConvertItem()
	{
		$hashTagFac    = new Hashtag(DI::logger());
		$mediaFac      = new Media(DI::logger(), DI::baseUrl());
		$urlFac        = new Url(DI::logger());
		$mentionFac    = new Mention(DI::logger(), DI::baseUrl());
		$activitiesFac = new Activities(DI::logger(), DI::baseUrl(), DI::twitterUser());
		$attachmentFac = new Attachment(DI::logger());

		$statusFac = new Status(DI::logger(), DI::dba(), DI::twitterUser(), $hashTagFac, $mediaFac, $urlFac, $mentionFac, $activitiesFac, $attachmentFac);
		$statusObj = $statusFac->createFromItemId(13, ApiTest::SELF_USER['id']);
		$status    = $statusObj->toArray();

		self::assertStringStartsWith('item_title', $status['text']);
		self::assertStringStartsWith('<h4>item_title</h4><br>perspiciatis impedit voluptatem', $status['html']);
	}

	/**
	 * Test the api_convert_item() function with an empty item body.
	 *
	 * @return void
	 */
	public function testApiConvertItemWithoutBody()
	{
		self::markTestIncomplete('Needs a dataset first');

		/*
		$result = api_convert_item(
			[
				'network' => 'feed',
				'title'   => 'item_title',
				'uri-id'  => -1,
				'body'    => '',
				'plink'   => 'item_plink'
			]
		);
		self::assertEquals("item_title", $result['text']);
		self::assertEquals('<h4>item_title</h4><br>item_plink', $result['html']);
		*/
	}

	/**
	 * Test the api_convert_item() function with the title in the body.
	 *
	 * @return void
	 */
	public function testApiConvertItemWithTitleInBody()
	{
		self::markTestIncomplete('Needs a dataset first');

		/*
		$result = api_convert_item(
			[
				'title'  => 'item_title',
				'body'   => 'item_title item_body',
				'uri-id' => 1,
			]
		);
		self::assertEquals('item_title item_body', $result['text']);
		self::assertEquals('<h4>item_title</h4><br>item_title item_body', $result['html']);
		*/
	}
}
