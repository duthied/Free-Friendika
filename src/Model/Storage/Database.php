<?php
/**
 * @file src/Model/Storage/Filesystem.php
 * @brief Storage backend system
 */

namespace Friendica\Model\Storage;

use Friendica\Core\L10n;
use Psr\Log\LoggerInterface;

/**
 * @brief Database based storage system
 *
 * This class manage data stored in database table.
 */
class Database implements IStorage
{
	const NAME = 'Database';

	/** @var \Friendica\Database\Database */
	private $dba;
	/** @var LoggerInterface */
	private $logger;
	/** @var L10n\L10n */
	private $l10n;

	/**
	 * @param \Friendica\Database\Database $dba
	 * @param LoggerInterface              $logger
	 * @param L10n\L10n                    $l10n
	 */
	public function __construct(\Friendica\Database\Database $dba, LoggerInterface $logger, L10n\L10n $l10n)
	{
		$this->dba    = $dba;
		$this->logger = $logger;
		$this->l10n   = $l10n;
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $reference)
	{
		$result = $this->dba->selectFirst('storage', ['data'], ['id' => $reference]);
		if (!$this->dba->isResult($result)) {
			return '';
		}

		return $result['data'];
	}

	/**
	 * @inheritDoc
	 */
	public function put(string $data, string $reference = '')
	{
		if ($reference !== '') {
			$result = $this->dba->update('storage', ['data' => $data], ['id' => $reference]);
			if ($result === false) {
				$this->logger->warning('Failed to update data.', ['id' => $reference, 'errorCode' =>  $this->dba->errorNo(), 'errorMessage' => $this->dba->errorMessage()]);
				throw new StorageException($this->l10n->t('Database storage failed to update %s', $reference));
 			}

			return $reference;
		} else {
			$result = $this->dba->insert('storage', ['data' => $data]);
			if ($result === false) {
				$this->logger->warning('Failed to insert data.', ['errorCode' =>  $this->dba->errorNo(), 'errorMessage' => $this->dba->errorMessage()]);
				throw new StorageException($this->l10n->t('Database storage failed to insert data'));
			}

			return $this->dba->lastInsertId();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function delete(string $reference)
	{
		return $this->dba->delete('storage', ['id' => $reference]);
	}

	/**
	 * @inheritDoc
	 */
	public function getOptions()
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function saveOptions(array $data)
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function __toString()
	{
		return self::NAME;
	}
}
