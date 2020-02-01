<?php

namespace Friendica\Test\src\Util\Emailer;

use Friendica\App\BaseURL;
use Friendica\Core\Config\IConfig;
use Friendica\Core\L10n;
use Friendica\Object\EMail\IEmail;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\SampleMailBuilder;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\EMailer\MailBuilder;
use Friendica\Util\EMailer\SystemMailBuilder;

class SystemMailBuilderTest extends MockedTest
{
	use VFSTrait;

	/** @var IConfig */
	private $config;
	/** @var L10n */
	private $l10n;
	/** @var BaseURL */
	private $baseUrl;

	/** @var string */
	private $defaultHeaders;

	public function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->config  = \Mockery::mock(IConfig::class);
		$this->config->shouldReceive('get')->with('config', 'admin_name')->andReturn('Admin');
		$this->l10n    = \Mockery::mock(L10n::class);
		$this->l10n->shouldReceive('t')->andReturnUsing(function ($msg) {
			return $msg;
		});
		$this->baseUrl = \Mockery::mock(BaseURL::class);
		$this->baseUrl->shouldReceive('getHostname')->andReturn('friendica.local');
		$this->baseUrl->shouldReceive('get')->andReturn('http://friendica.local');

		$this->defaultHeaders = "";
	}

	/**
	 * Test if the builder instance can get created
	 */
	public function testBuilderInstance()
	{
		$builder = new SystemMailBuilder($this->l10n, $this->baseUrl, $this->config, 'moreply@friendica.local', 'FriendicaSite');

		$this->assertInstanceOf(MailBuilder::class, $builder);
		$this->assertInstanceOf(SystemMailBuilder::class, $builder);
	}
}
