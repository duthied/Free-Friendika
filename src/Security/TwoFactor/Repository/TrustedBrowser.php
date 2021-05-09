<?php

namespace Friendica\Security\TwoFactor\Repository;

use Friendica\Security\TwoFactor\Model;
use Friendica\Security\TwoFactor\Collection\TrustedBrowsers;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\NotFoundException;
use Psr\Log\LoggerInterface;

class TrustedBrowser
{
	/** @var Database  */
	protected $db;

	/** @var LoggerInterface  */
	protected $logger;

	/** @var \Friendica\Security\TwoFactor\Factory\TrustedBrowser  */
	protected $factory;

	protected static $table_name = '2fa_trusted_browser';

	public function __construct(Database $database, LoggerInterface $logger, \Friendica\Security\TwoFactor\Factory\TrustedBrowser $factory = null)
	{
		$this->db = $database;
		$this->logger = $logger;
		$this->factory = $factory ?? new \Friendica\Security\TwoFactor\Factory\TrustedBrowser($logger);
	}

	/**
	 * @param string $cookie_hash
	 * @return Model\TrustedBrowser|null
	 * @throws \Exception
	 */
	public function selectOneByHash(string $cookie_hash): Model\TrustedBrowser
	{
		$fields = $this->db->selectFirst(self::$table_name, [], ['cookie_hash' => $cookie_hash]);
		if (!$this->db->isResult($fields)) {
			throw new NotFoundException('');
		}

		return $this->factory->createFromTableRow($fields);
	}

	public function selectAllByUid(int $uid): TrustedBrowsers
	{
		$rows = $this->db->selectToArray(self::$table_name, [], ['uid' => $uid]);

		$trustedBrowsers = [];
		foreach ($rows as $fields) {
			$trustedBrowsers[] = $this->factory->createFromTableRow($fields);
		}

		return new TrustedBrowsers($trustedBrowsers);
	}

	/**
	 * @param Model\TrustedBrowser $trustedBrowser
	 * @return bool
	 * @throws \Exception
	 */
	public function save(Model\TrustedBrowser $trustedBrowser): bool
	{
		return $this->db->insert(self::$table_name, $trustedBrowser->toArray(), $this->db::INSERT_UPDATE);
	}

	/**
	 * @param Model\TrustedBrowser $trustedBrowser
	 * @return bool
	 * @throws \Exception
	 */
	public function remove(Model\TrustedBrowser $trustedBrowser): bool
	{
		return $this->db->delete(self::$table_name, ['cookie_hash' => $trustedBrowser->cookie_hash]);
	}

	/**
	 * @param int    $local_user
	 * @param string $cookie_hash
	 * @return bool
	 * @throws \Exception
	 */
	public function removeForUser(int $local_user, string $cookie_hash): bool
	{
		return $this->db->delete(self::$table_name, ['cookie_hash' => $cookie_hash,'uid' => $local_user]);
	}

	/**
	 * @param int $local_user
	 * @return bool
	 * @throws \Exception
	 */
	public function removeAllForUser(int $local_user): bool
	{
		return $this->db->delete(self::$table_name, ['uid' => $local_user]);
	}
}
