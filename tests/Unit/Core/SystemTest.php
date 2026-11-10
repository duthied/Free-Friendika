<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core;

use Dice\Dice;
use Friendica\App\BaseURL;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Network\HTTPException\MovedPermanentlyException;
use Friendica\Network\HTTPException\TemporaryRedirectException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class SystemTest extends TestCase
{
	private function initDiWithBaseUrl(BaseURL&MockObject $baseUrl): void
	{
		$dice = $this->createMock(Dice::class);
		$dice->method('create')->willReturnCallback(fn (string $name): object => match ($name) {
			LoggerInterface::class => new NullLogger(),
			BaseURL::class         => $baseUrl,
			default                => throw new \InvalidArgumentException('Unexpected DI::create() call for class: ' . $name),
		});

		DI::init($dice, true);
	}

	public static function provideAbsoluteUrls(): array
	{
		return [
			'https'                    => ['https://friendica.other/profile/user'],
			'http'                     => ['http://example.com/path'],
			'customSchemeBare'         => ['icecubesapp://'],
			'customSchemeWithQuery'    => ['mona://?code=ABCDEF'],
			'customSchemeWithHost'     => ['phanpy://callback?code=ABCDEF'],
			'urn'                      => ['urn:ietf:wg:oauth:2.0:oob'],
			'ftp'                      => ['ftp://files.example.com/data'],
			'mailto'                   => ['mailto:user@example.com'],
			'customSchemeWithPlusDash' => ['myapp+native-v2://oauth/callback?state=xyz'],
		];
	}

	/**
	 * Absolute URIs — including custom-scheme OAuth callbacks without a host
	 * component (issue #15958) — must be redirected to directly and must never
	 * hit the BaseURL fallback that is reserved for relative paths.
	 */
	#[DataProvider('provideAbsoluteUrls')]
	public function testExternalRedirectWithAbsoluteUrlRedirects(string $url): void
	{
		$baseUrl = $this->createMock(BaseURL::class);
		$baseUrl->expects(self::never())->method('redirect');

		$this->initDiWithBaseUrl($baseUrl);

		self::expectException(FoundException::class);

		System::externalRedirect($url);
	}

	public static function provideRelativeUrls(): array
	{
		return [
			'bareWord'         => ['login'],
			'absolutePath'     => ['/profile/user'],
			'protocolRelative' => ['//example.com/path'],
			'startsWithDigit'  => ['1invalid://scheme'],
			'emptyString'      => [''],
		];
	}

	/**
	 * Paths without a URI scheme must be routed through BaseURL::redirect()
	 * rather than emitting a bare Location header.
	 */
	#[DataProvider('provideRelativeUrls')]
	public function testExternalRedirectWithNonAbsoluteUrlUsesBaseUrlFallback(string $url): void
	{
		$baseUrl = $this->createMock(BaseURL::class);
		$baseUrl->expects(self::once())->method('redirect')->with($url)
			->willThrowException(new FoundException());

		$this->initDiWithBaseUrl($baseUrl);

		self::expectException(FoundException::class);

		System::externalRedirect($url);
	}

	/**
	 * HTTP 301 redirects for absolute URIs must throw MovedPermanentlyException.
	 */
	public function testExternalRedirectWith301ThrowsMovedPermanentlyException(): void
	{
		$baseUrl = $this->createMock(BaseURL::class);
		$baseUrl->expects(self::never())->method('redirect');

		$this->initDiWithBaseUrl($baseUrl);

		self::expectException(MovedPermanentlyException::class);

		System::externalRedirect('https://example.com/new-location', 301);
	}

	/**
	 * HTTP 307 redirects for absolute URIs must throw TemporaryRedirectException.
	 */
	public function testExternalRedirectWith307ThrowsTemporaryRedirectException(): void
	{
		$baseUrl = $this->createMock(BaseURL::class);
		$baseUrl->expects(self::never())->method('redirect');

		$this->initDiWithBaseUrl($baseUrl);

		self::expectException(TemporaryRedirectException::class);

		System::externalRedirect('https://example.com/temporary', 307);
	}
}
