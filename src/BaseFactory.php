<?php

namespace Friendica;

use Psr\Log\LoggerInterface;

/**
 * Factories act as an intermediary to avoid direct Entitiy instanciation.
 *
 * @see BaseModel
 * @see BaseCollection
 */
abstract class BaseFactory
{
	/** @var LoggerInterface */
	protected $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}
}
