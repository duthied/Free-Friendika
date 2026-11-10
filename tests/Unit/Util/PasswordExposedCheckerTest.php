<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Util;

use Exception;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Util\HibpPasswordExposedChecker;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PasswordExposedCheckerTest extends TestCase
{
	public function testIsExposedReturnsTrueWhenSuffixFoundInHibpResponse(): void
	{
		$password = 'testpassword';
		$hash     = strtoupper(sha1($password));
		$prefix   = substr($hash, 0, 5);
		$suffix   = substr($hash, 5);

		$hibpResponse = "{$suffix}:42\nOTHERHASHSUFFIX:3\nYETANOTHERSUFFIX:1";

		$httpClient = $this->createMock(ICanSendHttpRequests::class);
		$httpClient->expects($this->once())
			->method('fetch')
			->with(
				'https://api.pwnedpasswords.com/range/' . $prefix,
				HttpClientAccept::TEXT,
				10,
				'',
				'',
			)
			->willReturn($hibpResponse);

		$cache = $this->createMock(ICanCache::class);
		$cache->expects($this->once())
			->method('get')
			->with('PasswordExposed:' . $prefix)
			->willReturn(null);
		$cache->expects($this->once())
			->method('set')
			->with('PasswordExposed:' . $prefix, $hibpResponse, Duration::MONTH);

		$logger = $this->createStub(LoggerInterface::class);

		$checker = new HibpPasswordExposedChecker($httpClient, $cache, $logger);

		$this->assertTrue($checker->isExposed($password));
	}

	public function testIsExposedReturnsFalseWhenSuffixNotFoundInHibpResponse(): void
	{
		$password = 'testpassword';
		$hash     = strtoupper(sha1($password));
		$prefix   = substr($hash, 0, 5);

		$hibpResponse = "SOMERANDOMSUFFIX:1\nANOTHERSUFFIX:5";

		$httpClient = $this->createMock(ICanSendHttpRequests::class);
		$httpClient->expects($this->once())
			->method('fetch')
			->with(
				'https://api.pwnedpasswords.com/range/' . $prefix,
				HttpClientAccept::TEXT,
				10,
				'',
				'',
			)
			->willReturn($hibpResponse);

		$cache = $this->createMock(ICanCache::class);
		$cache->expects($this->once())
			->method('get')
			->with('PasswordExposed:' . $prefix)
			->willReturn(null);
		$cache->expects($this->once())
			->method('set')
			->with('PasswordExposed:' . $prefix, $hibpResponse, Duration::MONTH);

		$logger = $this->createStub(LoggerInterface::class);

		$checker = new HibpPasswordExposedChecker($httpClient, $cache, $logger);

		$this->assertFalse($checker->isExposed($password));
	}

	public function testIsExposedReturnsFalseOnHttpError(): void
	{
		$password = 'testpassword';
		$hash     = strtoupper(sha1($password));
		$prefix   = substr($hash, 0, 5);

		$httpClient = $this->createMock(ICanSendHttpRequests::class);
		$httpClient->expects($this->once())
			->method('fetch')
			->with(
				'https://api.pwnedpasswords.com/range/' . $prefix,
				HttpClientAccept::TEXT,
				10,
				'',
				'',
			)
			->willThrowException(new Exception('Connection failed'));

		$cache = $this->createMock(ICanCache::class);
		$cache->expects($this->once())
			->method('get')
			->with('PasswordExposed:' . $prefix)
			->willReturn(null);
		$cache->expects($this->never())
			->method('set');

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('error')
			->with($this->stringContains('PasswordExposed check failed'));

		$checker = new HibpPasswordExposedChecker($httpClient, $cache, $logger);

		$this->assertFalse($checker->isExposed($password));
	}

	public function testIsExposedUsesCachedResponseWhenAvailable(): void
	{
		$password = 'testpassword';
		$hash     = strtoupper(sha1($password));
		$prefix   = substr($hash, 0, 5);
		$suffix   = substr($hash, 5);

		$cachedResponse = "{$suffix}:42\nOTHERHASH:3";

		$httpClient = $this->createMock(ICanSendHttpRequests::class);
		$httpClient->expects($this->never())
			->method('fetch');

		$cache = $this->createMock(ICanCache::class);
		$cache->expects($this->once())
			->method('get')
			->with('PasswordExposed:' . $prefix)
			->willReturn($cachedResponse);
		$cache->expects($this->never())
			->method('set');

		$logger = $this->createStub(LoggerInterface::class);

		$checker = new HibpPasswordExposedChecker($httpClient, $cache, $logger);

		$this->assertTrue($checker->isExposed($password));
	}

	public function testIsExposedReturnsFalseFromCacheWhenSuffixNotFound(): void
	{
		$password = 'testpassword';
		$hash     = strtoupper(sha1($password));
		$prefix   = substr($hash, 0, 5);

		$cachedResponse = "SOMERANDOMSUFFIX:1\nANOTHERSUFFIX:5";

		$httpClient = $this->createMock(ICanSendHttpRequests::class);
		$httpClient->expects($this->never())
			->method('fetch');

		$cache = $this->createMock(ICanCache::class);
		$cache->expects($this->once())
			->method('get')
			->with('PasswordExposed:' . $prefix)
			->willReturn($cachedResponse);
		$cache->expects($this->never())
			->method('set');

		$logger = $this->createStub(LoggerInterface::class);

		$checker = new HibpPasswordExposedChecker($httpClient, $cache, $logger);

		$this->assertFalse($checker->isExposed($password));
	}
}
