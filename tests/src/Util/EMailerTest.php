<?php

namespace Friendica\Test\src\Util;

use Friendica\App\BaseURL;
use Friendica\Core\Config\IConfig;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\IPConfig;
use Friendica\Object\EMail\IEmail;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\EmailerSpy;
use Friendica\Test\Util\HookMockTrait;
use Friendica\Test\Util\SampleMailBuilder;
use Friendica\Test\Util\VFSTrait;
use Mockery\MockInterface;
use Psr\Log\NullLogger;

/**
 * Annotation necessary because of Hook calls
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState false
 */
class EMailerTest extends MockedTest
{
	use VFSTrait;
	use HookMockTrait;

	/** @var IConfig|MockInterface */
	private $config;
	/** @var IPConfig|MockInterface */
	private $pConfig;
	/** @var L10n|MockInterface */
	private $l10n;
	/** @var BaseURL|MockInterface */
	private $baseUrl;

	/** @var string */
	private $defaultHeaders;

	protected function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->config  = \Mockery::mock(IConfig::class);
		$this->config->shouldReceive('get')->withArgs(['config', 'sender_email'])->andReturn('test@friendica.local')->once();
		$this->config->shouldReceive('get')->withArgs(['config', 'sitename', 'Friendica Social Network'])->andReturn('Friendica Social Network')->once();
		$this->config->shouldReceive('get')->withArgs(['system', 'sendmail_params', true])->andReturn(true);

		$this->pConfig = \Mockery::mock(IPConfig::class);
		$this->l10n    = \Mockery::mock(L10n::class);
		$this->baseUrl = \Mockery::mock(BaseURL::class);
		$this->baseUrl->shouldReceive('getHostname')->andReturn('friendica.local');
		$this->baseUrl->shouldReceive('get')->andReturn('http://friendica.local');

		$this->defaultHeaders = [];
	}

	protected function tearDown()
	{
		EmailerSpy::$MAIL_DATA = [];

		parent::tearDown();
	}

	public function testEmail()
	{
		$this->pConfig->shouldReceive('get')->withArgs(['1', 'system', 'email_textonly'])->andReturn(false)->once();

		$builder = new SampleMailBuilder($this->l10n, $this->baseUrl, $this->config, new NullLogger());

		$testEmail = $builder
			->withRecipient('recipient@friendica.local')
			->withMessage('Test Subject', "Test Message<b>Bold</b>", 'Test Text')
			->withSender('Sender', 'sender@friendica.loca')
			->forUser(['uid' => 1])
			->addHeader('Message-ID', 'first Id')
			->build(true);

		$emailer = new EmailerSpy($this->config, $this->pConfig, $this->baseUrl, new NullLogger(), $this->l10n);

		$this->assertTrue($emailer->send($testEmail));

		$this->assertContains("X-Friendica-Host : friendica.local", EmailerSpy::$MAIL_DATA['headers']);
		$this->assertContains("X-Friendica-Platform : Friendica", EmailerSpy::$MAIL_DATA['headers']);
		$this->assertContains("List-ID : <notification.friendica.local>", EmailerSpy::$MAIL_DATA['headers']);
		$this->assertContains("List-Archive : <http://friendica.local/notifications/system>", EmailerSpy::$MAIL_DATA['headers']);
		$this->assertContains("Reply-To: Sender <sender@friendica.loca>", EmailerSpy::$MAIL_DATA['headers']);
		$this->assertContains("MIME-Version: 1.0", EmailerSpy::$MAIL_DATA['headers']);
		// Base64 "Test Text"
		$this->assertContains(chunk_split(base64_encode('Test Text')), EmailerSpy::$MAIL_DATA['body']);
		// Base64 "Test Message<b>Bold</b>"
		$this->assertContains(chunk_split(base64_encode("Test Message<b>Bold</b>")), EmailerSpy::$MAIL_DATA['body']);
		$this->assertEquals("Test Subject", EmailerSpy::$MAIL_DATA['subject']);
		$this->assertEquals("recipient@friendica.local", EmailerSpy::$MAIL_DATA['to']);
		$this->assertEquals("-f sender@friendica.local", EmailerSpy::$MAIL_DATA['parameters']);
	}

	public function testWrongReturnTwoMessageIds()
	{
		/** @var IEmail $returnMail */
		$returnMail = null;

		$this->mockHookCallAll('emailer_send_prepare', $returnMail);

		$builder = new SampleMailBuilder($this->l10n, $this->baseUrl, $this->config, new NullLogger());

		$testEmail = $builder
			->withRecipient('recipient@friendica.local')
			->withMessage('Test Subject', "Test Message<b>Bold</b>", 'Test Text')
			->withSender('Sender', 'sender@friendica.loca')
			->forUser(['uid' => 1])
			->addHeader('Message-ID', 'first Id')
			->addHeader('Message-Id', 'second Id')
			->build(true);

		$emailer = new EmailerSpy($this->config, $this->pConfig, $this->baseUrl, new NullLogger(), $this->l10n);

		$this->assertFalse($emailer->send($testEmail));

		// check case sensitive key problem
		$this->assertArrayHasKey('Message-ID', $testEmail->getAdditionalMailHeader());
		$this->assertArrayHasKey('Message-Id', $testEmail->getAdditionalMailHeader());
	}
}
