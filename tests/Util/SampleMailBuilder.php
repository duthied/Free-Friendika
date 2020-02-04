<?php

namespace Friendica\Test\Util;

use Friendica\Util\EMailer\MailBuilder;

class SampleMailBuilder extends MailBuilder
{
	/** @var string */
	protected $subject;
	/** @var string */
	protected $html;
	/** @var string */
	protected $text;

	/**
	 * Adds a test message
	 *
	 * @param string $subject The subject of the email
	 * @param string $html    The preamble of the email
	 * @param string $text    The body of the email (if not set, the preamble will get used as body)
	 *
	 * @return static
	 */
	public function withMessage(string $subject, string $html, string $text)
	{
		$this->subject = $subject;
		$this->html    = $html;
		$this->text    = $text;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	protected function getSubject()
	{
		return $this->subject;
	}

	/**
	 * @inheritDoc
	 */
	protected function getHtmlMessage()
	{
		return $this->html;
	}

	/**
	 * @inheritDoc
	 */
	protected function getPlaintextMessage()
	{
		return $this->text;
	}
}
