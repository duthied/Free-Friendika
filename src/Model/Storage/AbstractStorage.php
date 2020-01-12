<?php

namespace Friendica\Model\Storage;

use Friendica\Core\L10n\L10n;
use Psr\Log\LoggerInterface;

/**
 * A general storage class which loads common dependencies and implements common methods
 */
abstract class AbstractStorage implements IStorage
{
	/** @var L10n */
	protected $l10n;
	/** @var LoggerInterface */
	protected $logger;

	/**
	 * @param L10n            $l10n
	 * @param LoggerInterface $logger
	 */
	public function __construct(L10n $l10n, LoggerInterface $logger)
	{
		$this->l10n   = $l10n;
		$this->logger = $logger;
	}

	public function __toString()
	{
		return static::getName();
	}
}
