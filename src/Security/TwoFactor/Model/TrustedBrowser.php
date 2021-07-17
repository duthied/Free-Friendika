<?php

namespace Friendica\Security\TwoFactor\Model;

use Friendica\BaseEntity;
use Friendica\Util\DateTimeFormat;

/**
 * Class TrustedBrowser
 *
 *
 * @property-read $cookie_hash
 * @property-read $uid
 * @property-read $user_agent
 * @property-read $created
 * @property-read $last_used
 * @package Friendica\Model\TwoFactor
 */
class TrustedBrowser extends BaseEntity
{
	protected $cookie_hash;
	protected $uid;
	protected $user_agent;
	protected $created;
	protected $last_used;

	/**
	 * Please do not use this constructor directly, instead use one of the method of the TrustedBroser factory.
	 *
	 * @see \Friendica\Security\TwoFactor\Factory\TrustedBrowser
	 *
	 * @param string      $cookie_hash
	 * @param int         $uid
	 * @param string      $user_agent
	 * @param string      $created
	 * @param string|null $last_used
	 */
	public function __construct(string $cookie_hash, int $uid, string $user_agent, string $created, string $last_used = null)
	{
		$this->cookie_hash = $cookie_hash;
		$this->uid = $uid;
		$this->user_agent = $user_agent;
		$this->created = $created;
		$this->last_used = $last_used;
	}

	public function recordUse()
	{
		$this->last_used = DateTimeFormat::utcNow();
	}
}
