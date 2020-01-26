<?php

namespace Friendica\Object;

use Friendica\Object\EMail\IEmail;

/**
 * The default implementation of the IEmail interface
 *
 * Provides the possibility to reuse the email instance with new recipients (@see EMail::withRecipient())
 */
class EMail implements IEmail
{
	/** @var string */
	private $fromName;
	/** @var string */
	private $fromEmail;
	/** @var string */
	private $replyTo;

	/** @var string */
	private $toEmail;

	/** @var string */
	private $subject;
	/** @var string */
	private $msgHtml;
	/** @var string */
	private $msgText;

	/** @var string */
	private $additionalMailHeader = '';
	/** @var int|null */
	private $toUid = null;

	public function __construct(string $fromName, string $fromEmail, string $replyTo, string $toEmail,
	                            string $subject, string $msgHtml, string $msgText,
	                            string $additionalMailHeader = '', int $toUid = null)
	{
		$this->fromName             = $fromName;
		$this->fromEmail            = $fromEmail;
		$this->replyTo              = $replyTo;
		$this->toEmail              = $toEmail;
		$this->subject              = $subject;
		$this->msgHtml              = $msgHtml;
		$this->msgText              = $msgText;
		$this->additionalMailHeader = $additionalMailHeader;
		$this->toUid                = $toUid;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFromName()
	{
		return $this->fromName;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFromEmail()
	{
		return $this->fromEmail;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getReplyTo()
	{
		return $this->replyTo;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getToEmail()
	{
		return $this->toEmail;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSubject()
	{
		return $this->subject;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMessage(bool $plain = false)
	{
		if ($plain) {
			return $this->msgText;
		} else {
			return $this->msgHtml;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAdditionalMailHeader()
	{
		return $this->additionalMailHeader;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRecipientUid()
	{
		return $this->toUid;
	}

	/**
	 * Returns the current email with a new recipient
	 *
	 * @param string $email The email of the recipient
	 * @param int    $uid   The (optional) UID of the recipient for further infos
	 *
	 * @return EMail
	 */
	public function withRecipient(string $email, int $uid = null)
	{
		$newEmail          = clone $this;
		$newEmail->toEmail = $email;
		$newEmail->toUid   = $uid;

		return $newEmail;
	}
}
