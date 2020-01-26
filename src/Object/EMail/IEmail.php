<?php

namespace Friendica\Object\EMail;

use Friendica\Util\Emailer;

/**
 * Interface for a single mail, which can be send through Emailer::send()
 *
 * @see Emailer::send()
 */
interface IEmail
{
	/**
	 * Gets the senders name for this email
	 *
	 * @return string
	 */
	function getFromName();

	/**
	 * Gets the senders email address for this email
	 *
	 * @return string
	 */
	function getFromEmail();

	/**
	 * Gets the UID of the sender of this email
	 *
	 * @return int|null
	 */
	function getRecipientUid();

	/**
	 * Gets the reply-to address for this email
	 *
	 * @return string
	 */
	function getReplyTo();

	/**
	 * Gets the senders email address
	 *
	 * @return string
	 */
	function getToEmail();

	/**
	 * Gets the subject of this email
	 *
	 * @return string
	 */
	function getSubject();

	/**
	 * Gets the message body of this email (either html or plaintext)
	 *
	 * @param boolean $text True, if returned as plaintext
	 *
	 * @return string
	 */
	function getMessage(bool $text = false);

	/**
	 * Gets any additional mail header
	 *
	 * @return string
	 */
	function getAdditionalMailHeader();
}
