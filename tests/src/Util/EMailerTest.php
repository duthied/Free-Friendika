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
 * @preserveGlobalState disabled
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
			->withSender('Sender', 'sender@friendica.local')
			->forUser(['uid' => 1])
			->addHeader('Message-ID', 'first Id')
			->build(true);

		$emailer = new EmailerSpy($this->config, $this->pConfig, $this->baseUrl, new NullLogger(), $this->l10n);

		self::assertTrue($emailer->send($testEmail));

		self::assertContains("X-Friendica-Host: friendica.local", EmailerSpy::$MAIL_DATA['headers']);
		self::assertContains("X-Friendica-Platform: Friendica", EmailerSpy::$MAIL_DATA['headers']);
		self::assertContains("List-ID: <notification.friendica.local>", EmailerSpy::$MAIL_DATA['headers']);
		self::assertContains("List-Archive: <http://friendica.local/notifications/system>", EmailerSpy::$MAIL_DATA['headers']);
		self::assertContains("Reply-To: Sender <sender@friendica.local>", EmailerSpy::$MAIL_DATA['headers']);
		self::assertContains("MIME-Version: 1.0", EmailerSpy::$MAIL_DATA['headers']);
		// Base64 "Test Text"
		self::assertContains(chunk_split(base64_encode('Test Text')), EmailerSpy::$MAIL_DATA['body']);
		// Base64 "Test Message<b>Bold</b>"
		self::assertContains(chunk_split(base64_encode("Test Message<b>Bold</b>")), EmailerSpy::$MAIL_DATA['body']);
		self::assertEquals("Test Subject", EmailerSpy::$MAIL_DATA['subject']);
		self::assertEquals("recipient@friendica.local", EmailerSpy::$MAIL_DATA['to']);
		self::assertEquals("-f sender@friendica.local", EmailerSpy::$MAIL_DATA['parameters']);
	}

	public function testTwoMessageIds()
	{
		$this->pConfig->shouldReceive('get')->withArgs(['1', 'system', 'email_textonly'])->andReturn(false)->once();

		/** @var IEmail $preparedEmail */
		$preparedEmail = null;
		/** @var IEmail $sentEMail */
		$sentEMail = null;

		$this->mockHookCallAll('emailer_send_prepare', $preparedEmail);
		$this->mockHookCallAll('emailer_send', $sentEMail);

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

		// even in case there are two message ids, send the mail anyway
		self::assertTrue($emailer->send($testEmail));

		// check case sensitive key problem
		self::assertArrayHasKey('Message-ID', $testEmail->getAdditionalMailHeader());
		self::assertArrayHasKey('Message-Id', $testEmail->getAdditionalMailHeader());
	}
}
