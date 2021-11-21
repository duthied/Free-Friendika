<?php

namespace Friendica\Test\src\Module\Api;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\Core\L10n;
use Friendica\Factory\Api\Twitter\User;
use Friendica\Module\Api\ApiResponse;
use Friendica\Test\MockedTest;
use Psr\Log\NullLogger;

class ApiResponseTest extends MockedTest
{
	public function testErrorWithJson()
	{
		$l10n = \Mockery::mock(L10n::class);
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);
		$response->error(200, 'OK', 'error_message', 'json');

		self::assertEquals('{"error":"error_message","code":"200 OK","request":""}', $response->getContent());
	}

	public function testErrorWithXml()
	{
		$l10n = \Mockery::mock(L10n::class);
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);
		$response->error(200, 'OK', 'error_message', 'xml');

		self::assertEquals('<?xml version="1.0"?>' . "\n" .
						   '<status xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" ' .
						   'xmlns:friendica="http://friendi.ca/schema/api/1/" ' .
						   'xmlns:georss="http://www.georss.org/georss">' . "\n" .
						   '  <error>error_message</error>' . "\n" .
						   '  <code>200 OK</code>' . "\n" .
						   '  <request/>' . "\n" .
						   '</status>' . "\n",
			$response->getContent());
	}

	public function testErrorWithRss()
	{
		$l10n = \Mockery::mock(L10n::class);
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);
		$response->error(200, 'OK', 'error_message', 'rss');

		self::assertEquals(
			'<?xml version="1.0"?>' . "\n" .
			'<status xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" ' .
			'xmlns:friendica="http://friendi.ca/schema/api/1/" ' .
			'xmlns:georss="http://www.georss.org/georss">' . "\n" .
			'  <error>error_message</error>' . "\n" .
			'  <code>200 OK</code>' . "\n" .
			'  <request/>' . "\n" .
			'</status>' . "\n",
			$response->getContent());
	}

	public function testErrorWithAtom()
	{
		$l10n = \Mockery::mock(L10n::class);
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);
		$response->error(200, 'OK', 'error_message', 'atom');

		self::assertEquals(
			'<?xml version="1.0"?>' . "\n" .
			'<status xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" ' .
			'xmlns:friendica="http://friendi.ca/schema/api/1/" ' .
			'xmlns:georss="http://www.georss.org/georss">' . "\n" .
			'  <error>error_message</error>' . "\n" .
			'  <code>200 OK</code>' . "\n" .
			'  <request/>' . "\n" .
			'</status>' . "\n",
			$response->getContent());
	}

	public function testUnsupported()
	{
		$l10n = \Mockery::mock(L10n::class);
		$l10n->shouldReceive('t')->andReturnUsing(function ($args) {
			return $args;
		});
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);
		$response->unsupported();

		self::assertEquals('{"error":"API endpoint %s %s is not implemented","error_description":"The API endpoint is currently not implemented but might be in the future."}', $response->getContent());
	}
}
