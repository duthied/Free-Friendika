<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core;

use Friendica\Core\EarlyExitException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class EarlyExitExceptionTest extends TestCase
{
	public function testConstructorStoresResponse(): void
	{
		$response = $this->createStub(ResponseInterface::class);

		$exception = new EarlyExitException($response);

		self::assertSame($response, $exception->getResponse());
	}

	public function testDefaultMessage(): void
	{
		$response  = $this->createStub(ResponseInterface::class);
		$exception = new EarlyExitException($response);

		self::assertSame('Module requested early exit', $exception->getMessage());
	}

	public function testIsRuntimeException(): void
	{
		$response  = $this->createStub(ResponseInterface::class);
		$exception = new EarlyExitException($response);

		self::assertInstanceOf(\RuntimeException::class, $exception); // @phpstan-ignore staticMethod.alreadyNarrowedType (intentional type hierarchy documentation)
	}
}
