<?php

namespace Friendica\Object\Api\Friendica;

use Friendica\BaseEntity;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Model\Notify;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

/**
 * Friendica Notification
 *
 * @see https://github.com/friendica/friendica/blob/develop/doc/API-Entities.md#notification
 */
class Notification extends BaseEntity
{
	/** @var integer */
	protected $id;
	/** @var string */
	protected $hash;
	/** @var integer */
	protected $type;
	/** @var string Full name of the contact subject */
	protected $name;
	/** @var string Profile page URL of the contact subject */
	protected $url;
	/** @var string Profile photo URL of the contact subject */
	protected $photo;
	/** @var string YYYY-MM-DD hh:mm:ss local server time */
	protected $date;
	/** @var string The message (BBCode) */
	protected $msg;
	/** @var integer Owner User Id */
	protected $uid;
	/** @var string Notification URL */
	protected $link;
	/** @var integer Item Id */
	protected $iid;
	/** @var integer Parent Item Id */
	protected $parent;
	/** @var boolean  Whether the notification was read or not. */
	protected $seen;
	/** @var string Verb URL @see http://activitystrea.ms */
	protected $verb;
	/** @var string Subject type (`item`, `intro` or `mail`) */
	protected $otype;
	/** @var string Full name of the contact subject (HTML) */
	protected $name_cache;
	/** @var string Plaintext version of the notification text with a placeholder (`{0}`) for the subject contact's name. (Plaintext) */
	protected $msg_cache;
	/** @var integer  Unix timestamp */
	protected $timestamp;
	/** @var string Time since the note was posted, eg "1 hour ago" */
	protected $date_rel;
	/** @var string Message (HTML) */
	protected $msg_html;
	/** @var string Message (Plaintext) */
	protected $msg_plain;

	public function __construct(Notify $notify)
	{
		// map each notify attribute to the entity
		foreach ($notify->toArray() as $key => $value) {
			$this->{$key} = $value;
		}

		// add additional attributes for the API
		try {
			$this->timestamp = strtotime(DateTimeFormat::local($this->date));
			$this->msg_html  = BBCode::convert($this->msg, false);
			$this->msg_plain = explode("\n", trim(HTML::toPlaintext($this->msg_html, 0)))[0];
		} catch (\Exception $e) {
		}

		$this->date_rel = Temporal::getRelativeDate($this->date);
	}
}
