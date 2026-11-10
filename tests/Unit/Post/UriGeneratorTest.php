<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Post;

use Dice\Dice;
use Friendica\App\BaseURL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\DI;
use Friendica\Post\UriGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class UriGeneratorTest extends TestCase
{
	private static function createBaseURL(): BaseURL
	{
		$config = self::createStub(IManageConfigValues::class);
		$config->method('get')->willReturnCallback(function (string $category, string $key, mixed $default = null): mixed {
			if ($category === 'system' && $key === 'url') {
				return 'https://friendica.local';
			}

			return $default;
		});

		return new BaseURL($config, new NullLogger());
	}

	public static function provideGuidFromUriTestData(): array
	{
		return [
			'https' => [
				'uri'    => 'https://example.com/objects/12345',
				'host'   => null,
				'expect' => '4478759c-65ec4c0b-2f66437a957135fa',
			],
			// The scheme must not influence the GUID
			'http' => [
				'uri'    => 'http://example.com/objects/12345',
				'host'   => null,
				'expect' => '4478759c-65ec4c0b-2f66437a957135fa',
			],
			// An explicit host only changes the host prefix, not the URI hashes
			'hostOverride' => [
				'uri'    => 'https://example.com/objects/12345',
				'host'   => 'other.host',
				'expect' => '7a0b733a-65ec4c0b-2f66437a957135fa',
			],
			'withoutHost' => [
				'uri'    => 'no scheme here',
				'host'   => null,
				'expect' => '00000000-7c8359f4-6497fff7346911ed',
			],
			'malformedUri' => [
				'uri'    => ':',
				'host'   => null,
				'expect' => '00000000-24b9c963-af63bd4c8601b7e5',
			],
		];
	}

	/**
	 * The generated GUIDs are used for deduplication across the federation,
	 * so their values must stay stable.
	 */
	#[DataProvider('provideGuidFromUriTestData')]
	public function testGuidFromUriReturnsStableGuid(string $uri, ?string $host, string $expect): void
	{
		$generator = new UriGenerator(self::createBaseURL(), new NullLogger());

		self::assertSame($expect, $generator->guidFromUri($uri, $host));
	}

	public function testGuidFromUriWithoutHostLogsWarning(): void
	{
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())->method('warning')->with('Empty host GUID part');

		$generator = new UriGenerator(self::createBaseURL(), $logger);

		$generator->guidFromUri('no scheme here');
	}

	public function testNewURIWithGuid(): void
	{
		$generator = new UriGenerator(self::createBaseURL(), new NullLogger());

		self::assertSame('https://friendica.local/objects/12345-abcde', $generator->newURI('12345-abcde'));
	}

	public function testNewURIWithoutGuidGeneratesUuid(): void
	{
		$baseUrl = self::createBaseURL();

		// System::createUUID() resolves the base URL host via the DI container
		$dice = $this->createMock(Dice::class);
		$dice->method('create')->willReturnCallback(fn (string $name): object => match ($name) {
			LoggerInterface::class => new NullLogger(),
			BaseURL::class         => $baseUrl,
			default                => throw new \LogicException('Unexpected class requested: ' . $name),
		});

		DI::init($dice, true);

		$generator = new UriGenerator($baseUrl, new NullLogger());

		self::assertMatchesRegularExpression(
			'#^https://friendica\.local/objects/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$#',
			$generator->newURI(),
		);
	}
}
