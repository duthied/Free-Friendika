<?php

namespace Friendica\Model;

use Exception;
use Friendica\BaseModel;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;
use Psr\Log\LoggerInterface;

/**
 * Model for an entry in the notify table
 * - Including additional, calculated properties
 *
 * Is used either for frontend interactions or for API-based interaction
 * @see https://github.com/friendica/friendica/blob/develop/doc/API-Entities.md#notification
 *
 * @property string  hash
 * @property integer type
 * @property string  name   Full name of the contact subject
 * @property string  url    Profile page URL of the contact subject
 * @property string  photo  Profile photo URL of the contact subject
 * @property string  date   YYYY-MM-DD hh:mm:ss local server time
 * @property string  msg
 * @property integer uid  	Owner User Id
 * @property string  link   Notification URL
 * @property integer iid  	Item Id
 * @property integer parent Parent Item Id
 * @property boolean seen   Whether the notification was read or not.
 * @property string  verb   Verb URL (@see http://activitystrea.ms)
 * @property string  otype  Subject type (`item`, `intro` or `mail`)
 *
 * @property-read string name_cache Full name of the contact subject
 * @property-read string msg_cache  Plaintext version of the notification text with a placeholder (`{0}`) for the subject contact's name.
 *
 * @property-read integer timestamp  Unix timestamp
 * @property-read string  dateRel  	 Time since the note was posted, eg "1 hour ago"
 * @property-read string  $msg_html
 * @property-read string  $msg_plain
 */
class Notification extends BaseModel
{
	/** @var \Friendica\Repository\Notification */
	private $repo;

	public function __construct(Database $dba, LoggerInterface $logger, \Friendica\Repository\Notification $repo, array $data = [])
	{
		parent::__construct($dba, $logger, $data);

		$this->repo = $repo;

		$this->setNameCache();
		$this->setTimestamp();
		$this->setMsg();
	}

	/**
	 * Set the notification as seen
	 *
	 * @param bool $seen true, if seen
	 *
	 * @return bool True, if the seen state could be saved
	 */
	public function setSeen(bool $seen = true)
	{
		$this->seen = $seen;
		try {
			return $this->repo->update($this);
		} catch (Exception $e) {
			$this->logger->warning('Update failed.', ['$this' => $this, 'exception' => $e]);
			return false;
		}
	}

	/**
	 * Set some extra properties to the notification from db:
	 *  - timestamp as int in default TZ
	 *  - date_rel : relative date string
	 */
	private function setTimestamp()
	{
		try {
			$this->timestamp = strtotime(DateTimeFormat::local($this->date));
		} catch (Exception $e) {
		}
		$this->dateRel = Temporal::getRelativeDate($this->date);
	}

	/**
	 * Sets the pre-formatted name (caching)
	 *
	 * @throws InternalServerErrorException
	 */
	private function setNameCache()
	{
		$this->name_cache = strip_tags(BBCode::convert($this->source_name ?? ''));
	}

	/**
	 * Set some extra properties to the notification from db:
	 *  - msg_html: message as html string
	 *  - msg_plain: message as plain text string
	 *  - msg_cache: The pre-formatted message (caching)
	 */
	private function setMsg()
	{
		try {
			$this->msg_html  = BBCode::convert($this->msg, false);
			$this->msg_plain = explode("\n", trim(HTML::toPlaintext($this->msg_html, 0)))[0];
			$this->msg_cache = self::formatMessage($this->name_cache, strip_tags(BBCode::convert($this->msg)));
		} catch (InternalServerErrorException $e) {
		}
	}

	public function __set($name, $value)
	{
		parent::__set($name, $value);

		if ($name == 'date') {
			$this->setTimestamp();
		}

		if ($name == 'msg') {
			$this->setMsg();
		}

		if ($name == 'source_name') {
			$this->setNameCache();
		}
	}

	/**
	 * Formats a notification message with the notification author
	 *
	 * Replace the name with {0} but ensure to make that only once. The {0} is used
	 * later and prints the name in bold.
	 *
	 * @param string $name
	 * @param string $message
	 *
	 * @return string Formatted message
	 */
	public static function formatMessage($name, $message)
	{
		if ($name != '') {
			$pos = strpos($message, $name);
		} else {
			$pos = false;
		}

		if ($pos !== false) {
			$message = substr_replace($message, '{0}', $pos, strlen($name));
		}

		return $message;
	}
}
