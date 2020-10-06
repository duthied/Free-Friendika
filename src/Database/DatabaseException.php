<?php
declare(strict_types=1);

namespace Friendica\Database;

use Exception;
use Throwable;

/**
 * A database fatal exception, which shouldn't occur
 */
class DatabaseException extends Exception
{
	protected $query;

	/**
	 * Construct the exception. Note: The message is NOT binary safe.
	 *
	 * @link https://php.net/manual/en/exception.construct.php
	 *
	 * @param string    $message  The Database error message.
	 * @param int       $code     The Database error code.
	 * @param string    $query    The Database error query.
	 * @param Throwable $previous [optional] The previous throwable used for the exception chaining.
	 */
	public function __construct(string $message, int $code, string $query, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
		$this->query = $query;
	}

	/**
	 * {@inheritDoc}
	 */
	public function __toString()
	{
		return sprintf('Database error %d "%s" at "%s"', $this->message, $this->code, $this->query);
	}
}
