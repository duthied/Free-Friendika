<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\App;

use Friendica\App\Page;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Test the Page class, specifically the SPA-related functionality
 */
class PageTest extends TestCase
{
	/**
	 * Test that page content can be accessed via ArrayAccess
	 */
	public function testPageArrayAccess(): void
	{
		$basePath        = '/var/www';
		$eventDispatcher = $this->createStub(EventDispatcherInterface::class);

		$page = new Page($basePath, $eventDispatcher);

		// Test setting and getting values via ArrayAccess
		$page['content']  = '<div>Array Access Test</div>';
		$page['title']    = 'Array Access Title';
		$page['htmlhead'] = '<meta name="test">';
		$page['nav']      = '<nav>Navigation</nav>';
		$page['footer']   = '<footer>Footer</footer>';

		self::assertEquals('<div>Array Access Test</div>', $page['content']);
		self::assertEquals('Array Access Title', $page['title']);
		self::assertEquals('<meta name="test">', $page['htmlhead']);
		self::assertEquals('<nav>Navigation</nav>', $page['nav']);
		self::assertEquals('<footer>Footer</footer>', $page['footer']);

		// Test isset
		self::assertTrue(isset($page['content']));
		self::assertFalse(isset($page['nonexistent']));

		// Test unset
		unset($page['footer']);
		self::assertNull($page['footer']);
		self::assertFalse(isset($page['footer']));
	}

	/**
	 * Test that page can store and retrieve various content types
	 */
	public function testPageContentTypes(): void
	{
		$basePath        = '/var/www';
		$eventDispatcher = $this->createStub(EventDispatcherInterface::class);

		$page = new Page($basePath, $eventDispatcher);

		// Test with empty content
		$page['content'] = '';
		self::assertEquals('', $page['content']);

		// Test with HTML content
		$page['content'] = '<p>Test paragraph</p>';
		self::assertEquals('<p>Test paragraph</p>', $page['content']);

		// Test with special characters
		$page['title'] = 'Test & "Title"';
		self::assertEquals('Test & "Title"', $page['title']);

		// Test with multiline content
		$page['htmlhead'] = "<meta charset=\"utf-8\">\n<meta name=\"viewport\">";
		self::assertStringContainsString('<meta charset="utf-8">', $page['htmlhead']);
		self::assertStringContainsString('<meta name="viewport">', $page['htmlhead']);
	}

	/**
	 * Test that page preserves content across multiple operations
	 */
	public function testPageContentPreservation(): void
	{
		$basePath        = '/var/www';
		$eventDispatcher = $this->createStub(EventDispatcherInterface::class);

		$page = new Page($basePath, $eventDispatcher);

		// Set initial content
		$page['content']  = 'Initial Content';
		$page['title']    = 'Initial Title';
		$page['htmlhead'] = 'Initial Head';

		// Read and verify
		self::assertEquals('Initial Content', $page['content']);
		self::assertEquals('Initial Title', $page['title']);
		self::assertEquals('Initial Head', $page['htmlhead']);

		// Update only one field
		$page['content'] = 'Updated Content';

		// Verify others are preserved
		self::assertEquals('Updated Content', $page['content']);
		self::assertEquals('Initial Title', $page['title']);
		self::assertEquals('Initial Head', $page['htmlhead']);
	}

	/**
	 * Test that page can handle null values
	 */
	public function testPageNullHandling(): void
	{
		$basePath        = '/var/www';
		$eventDispatcher = $this->createStub(EventDispatcherInterface::class);

		$page = new Page($basePath, $eventDispatcher);

		// Set null values
		$page['content'] = null;
		$page['title']   = null;

		self::assertNull($page['content']);
		self::assertNull($page['title']);

		// Set to string and back to null
		$page['content'] = 'Test';
		self::assertEquals('Test', $page['content']);

		$page['content'] = null;
		self::assertNull($page['content']);
	}

	/**
	 * Test that page properly handles numeric keys
	 */
	public function testPageNumericKeys(): void
	{
		$basePath        = '/var/www';
		$eventDispatcher = $this->createStub(EventDispatcherInterface::class);

		$page = new Page($basePath, $eventDispatcher);

		// Test with numeric keys (some internal page data uses numeric indices)
		$page[0] = 'numeric zero';
		$page[1] = 'numeric one';

		self::assertEquals('numeric zero', $page[0]);
		self::assertEquals('numeric one', $page[1]);

		// Test isset with numeric keys
		self::assertTrue(isset($page[0]));
		self::assertTrue(isset($page[1]));
		self::assertFalse(isset($page[2]));
	}
}
