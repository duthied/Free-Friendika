<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Integration\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Profiler;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class BaseModuleTest extends TestCase
{
	public function testHandleRequestGetMethodReturnsResponse(): void
	{
		$module = $this->createModule(['getMethod' => 'GET']);

		$module->method('content')->willReturn('Hello World');

		$request = new ServerRequest('GET', 'https://friendica.local/test');
		$result  = $module->handleRequest($request);

		$this->assertEquals(200, $result->getStatusCode());
		$this->assertEquals('Hello World', (string) $result->getBody());
	}

	public function testHandleRequestPostMethodPassesParsedBody(): void
	{
		$module = $this->createModule(['getMethod' => 'POST']);

		$requestContent = null;
		$module->method('content')->willReturnCallback(function (array $request) use (&$requestContent) {
			$requestContent = $request;
			return 'ok';
		});

		$request = new ServerRequest('POST', 'https://friendica.local/test');
		$request = $request->withParsedBody(['key' => 'value']);
		$result  = $module->handleRequest($request);

		$this->assertEquals(200, $result->getStatusCode());
		$this->assertEquals(['key' => 'value'], $requestContent);
	}

	public function testHandleRequestThrowsExceptionWithoutHttpException(): void
	{
		$module = $this->createModule(['getMethod' => 'GET']);

		$module->method('content')->willThrowException(new NotFoundException());

		$request = new ServerRequest('GET', 'https://friendica.local/test');

		$this->expectException(NotFoundException::class);
		$module->handleRequest($request);
	}

	public function testHandleRequestQueryParamsAreAccessibleInContent(): void
	{
		$module = $this->createModule(['getMethod' => 'GET']);

		$requestContent = null;
		$module->method('content')->willReturnCallback(function (array $request) use (&$requestContent) {
			$requestContent = $request;
			return 'ok';
		});

		$request = new ServerRequest('GET', 'https://friendica.local/test');
		$request = $request->withQueryParams(['id' => '42']);
		$result  = $module->handleRequest($request);

		$this->assertEquals(200, $result->getStatusCode());
		$this->assertEquals(['id' => '42'], $requestContent);
	}

	private function createModule(array $options = []): BaseModule&MockObject
	{
		$httpMethod = $options['getMethod'] ?? 'GET';

		$args = $this->createMock(App\Arguments::class);
		$args->method('getQueryString')->willReturn('test');
		$args->method('getModuleName')->willReturn('Test');
		$args->method('getMethod')->willReturn($httpMethod);

		$eventDispatcher = $this->createMock(EventDispatcherInterface::class);
		$eventDispatcher->method('dispatch')->willReturnArgument(0);

		return $this->getMockBuilder(BaseModule::class)
			->setConstructorArgs([
				$this->createStub(L10n::class),
				$this->createStub(App\BaseURL::class),
				$args,
				$this->createStub(LoggerInterface::class),
				$this->createStub(Profiler::class),
				new Response(),
				[],
				[],
				$eventDispatcher,
			])
			->onlyMethods(['content'])
			->getMock();
	}
}
