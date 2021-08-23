<?php

namespace Friendica\Test\src\Network;

use Dice\Dice;
use Friendica\DI;
use Friendica\Network\HTTPClient;
use Friendica\Network\IHTTPClient;
use Friendica\Test\MockedTest;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use mattwright\URLResolver;
use Psr\Log\NullLogger;

require_once __DIR__ . '/../../../static/dbstructure.config.php';

class HTTPRequestTest extends MockedTest
{
	/** @var HandlerStack */
	protected $handler;

	protected function setUp(): void
	{
		parent::setUp();

		$this->handler = HandlerStack::create();

		$client = new Client(['handler' => $this->handler]);

		$resolver = \Mockery::mock(URLResolver::class);

		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('startRecording')->andReturnTrue();
		$profiler->shouldReceive('stopRecording')->andReturnTrue();

		$httpClient = new HTTPClient(new NullLogger(), $profiler, $client, $resolver);

		$dice    = DI::getDice();
		$newDice = \Mockery::mock($dice)->makePartial();
		$newDice->shouldReceive('create')->with(IHTTPClient::class)->andReturn($httpClient);
		DI::init($newDice);
	}

	public function dataImages()
	{
		return [
			'image1' => [
				'url'     => 'https://pbs.twimg.com/profile_images/2365515285/9re7kx4xmc0eu9ppmado.png',
				'headers' => [
					'Server'                        => 'tsa_b',
					'Content-Type'                  => 'image/png',
					'Cache-Control'                 => 'max-age=604800,must-revalidate',
					'Last-Modified'                 => 'Thu,04Nov201001:42:54GMT',
					'Content-Length'                => '24875',
					'Access-Control-Allow-Origin'   => '*',
					'Access-Control-Expose-Headers' => 'Content-Length',
					'Date'                          => 'Mon,23Aug202112:39:00GMT',
					'Connection'                    => 'keep-alive',
				],
				'data'      => file_get_contents(__DIR__ . '/../../datasets/curl/image.content'),
				'assertion' => [
					'0'    => '400',
					'1'    => '400',
					'2'    => '3',
					'3'    => 'width="400" height="400"',
					'bits' => '8',
					'mime' => 'image/png',
					'size' => '24875',
				]
			]
		];
	}

	/**
	 * @dataProvider dataImages
	 */
	public function testGetInfoFromURL(string $url, array $headers, string $data, array $assertion)
	{
		$this->handler->setHandler(new MockHandler([
			new Response(200, $headers, $data),
		]));

		self::assertArraySubset($assertion, Images::getInfoFromURL($url));
	}
}
