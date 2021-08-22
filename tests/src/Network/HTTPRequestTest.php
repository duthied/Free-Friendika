<?php

namespace Friendica\Test\src\Network;

use Dice\Dice;
use Friendica\App\BaseURL;
use Friendica\Core\Config\IConfig;
use Friendica\DI;
use Friendica\Network\HTTPRequest;
use Friendica\Network\IHTTPRequest;
use Friendica\Test\MockedTest;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;

require_once __DIR__ . '/../../../static/dbstructure.config.php';

class HTTPRequestTest extends MockedTest
{
	public function testImageFetch()
	{
		$mock = new MockHandler([
			new Response(200, [
				'Server' => 'tsa_b',
				'Content-Type' => 'image/png',
				'Cache-Control' => 'max-age=604800, must-revalidate',
				'Content-Length' => 24875,
			], file_get_contents(__DIR__ . '/../../datasets/curl/image.content'))
		]);

		$config = \Mockery::mock(IConfig::class);
		$config->shouldReceive('get')->with('system', 'curl_range_bytes', 0)->once()->andReturn(null);
		$config->shouldReceive('get')->with('system', 'verifyssl')->once();
		$config->shouldReceive('get')->with('system', 'proxy')->once();
		$config->shouldReceive('get')->with('system', 'ipv4_resolve', false)->once()->andReturnFalse();
		$config->shouldReceive('get')->with('system', 'blocklist', [])->once()->andReturn([]);

		$baseUrl = \Mockery::mock(BaseURL::class);
		$baseUrl->shouldReceive('get')->andReturn('http://friendica.local');

		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('startRecording')->andReturnTrue();
		$profiler->shouldReceive('stopRecording')->andReturnTrue();

		$httpRequest = new HTTPRequest(new NullLogger(), $profiler, $config, $baseUrl);

		self::assertInstanceOf(IHTTPRequest::class, $httpRequest);

		$dice = \Mockery::mock(Dice::class);
		$dice->shouldReceive('create')->with(IHTTPRequest::class)->andReturn($httpRequest)->once();
		$dice->shouldReceive('create')->with(BaseURL::class)->andReturn($baseUrl);
		$dice->shouldReceive('create')->with(IConfig::class)->andReturn($config)->once();

		DI::init($dice);

		print_r(Images::getInfoFromURL('https://pbs.twimg.com/profile_images/2365515285/9re7kx4xmc0eu9ppmado.png'));
	}
}
