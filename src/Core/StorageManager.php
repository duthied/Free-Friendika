<?php

namespace Friendica\Core;

use Dice\Dice;
use Exception;
use Friendica\Core\Config\IConfiguration;
use Friendica\Database\Database;
use Friendica\Model\Storage;
use Psr\Log\LoggerInterface;


/**
 * @brief Manage storage backends
 *
 * Core code uses this class to get and set current storage backend class.
 * Addons use this class to register and unregister additional backends.
 */
class StorageManager
{
	// Default tables to look for data
	const TABLES = ['photo', 'attach'];

	// Default storage backends
	const DEFAULT_BACKENDS = [
		Storage\Filesystem::NAME => Storage\Filesystem::class,
		Storage\Database::NAME   => Storage\Database::class,
	];

	private $backends = [];

	/** @var Database */
	private $dba;
	/** @var IConfiguration */
	private $config;
	/** @var LoggerInterface */
	private $logger;
	/** @var Dice */
	private $dice;

	/** @var Storage\IStorage */
	private $currentBackend;

	/**
	 * @param Database        $dba
	 * @param IConfiguration  $config
	 * @param LoggerInterface $logger
	 */
	public function __construct(Database $dba, IConfiguration $config, LoggerInterface $logger, Dice $dice)
	{
		$this->dba      = $dba;
		$this->config   = $config;
		$this->logger   = $logger;
		$this->dice     = $dice;
		$this->backends = $config->get('storage', 'backends', self::DEFAULT_BACKENDS);

		$currentName = $this->config->get('storage', 'name', '');

		if ($this->isValidBackend($currentName)) {
			$this->currentBackend = $this->dice->create($this->backends[$currentName]);
		} else {
			$this->currentBackend = null;
		}
	}

	/**
	 * @brief Return current storage backend class
	 *
	 * @return Storage\IStorage|null
	 */
	public function getBackend()
	{
		return $this->currentBackend;
	}

	/**
	 * @brief Return storage backend class by registered name
	 *
	 * @param string $name Backend name
	 *
	 * @return Storage\IStorage|null null if no backend registered at $name
	 */
	public function getByName(string $name)
	{
		if (!$this->isValidBackend($name)) {
			return null;
		}

		return $this->dice->create($this->backends[$name]);
	}

	/**
	 * Checks, if the storage is a valid backend
	 *
	 * @param string $name The name or class of the backend
	 *
	 * @return boolean True, if the backend is a valid backend
	 */
	public function isValidBackend(string $name)
	{
		return array_key_exists($name, $this->backends);
	}

	/**
	 * @brief Set current storage backend class
	 *
	 * @param string $name Backend class name
	 *
	 * @return boolean True, if the set was successful
	 */
	public function setBackend(string $name)
	{
		if (!$this->isValidBackend($name)) {
			return false;
		}

		if ($this->config->set('storage', 'name', $name)) {
			$this->currentBackend = $this->dice->create($this->backends[$name]);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @brief Get registered backends
	 *
	 * @return array
	 */
	public function listBackends()
	{
		return $this->backends;
	}

	/**
	 * @brief Register a storage backend class
	 *
	 * @param string $name  User readable backend name
	 * @param string $class Backend class name
	 *
	 * @return boolean True, if the registration was successful
	 */
	public function register(string $name, string $class)
	{
		if (!is_subclass_of($class, Storage\IStorage::class)) {
			return false;
		}

		$backends        = $this->backends;
		$backends[$name] = $class;

		if ($this->config->set('storage', 'backends', $this->backends)) {
			$this->backends = $backends;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @brief Unregister a storage backend class
	 *
	 * @param string $name User readable backend name
	 *
	 * @return boolean True, if unregistering was successful
	 */
	public function unregister(string $name)
	{
		unset($this->backends[$name]);
		return $this->config->set('storage', 'backends', $this->backends);
	}

	/**
	 * @brief Move up to 5000 resources to storage $dest
	 *
	 * Copy existing data to destination storage and delete from source.
	 * This method cannot move to legacy in-table `data` field.
	 *
	 * @param Storage\IStorage $destination Destination storage class name
	 * @param array            $tables      Tables to look in for resources. Optional, defaults to ['photo', 'attach']
	 * @param int              $limit       Limit of the process batch size, defaults to 5000
	 *
	 * @return int Number of moved resources
	 * @throws Storage\StorageException
	 * @throws Exception
	 */
	public function move(Storage\IStorage $destination, array $tables = self::TABLES, int $limit = 5000)
	{
		if ($destination === null) {
			throw new Storage\StorageException('Can\'t move to NULL storage backend');
		}

		$moved = 0;
		foreach ($tables as $table) {
			// Get the rows where backend class is not the destination backend class
			$resources = $this->dba->select(
				$table,
				['id', 'data', 'backend-class', 'backend-ref'],
				['`backend-class` IS NULL or `backend-class` != ?', $destination],
				['limit' => $limit]
			);

			while ($resource = $this->dba->fetch($resources)) {
				$id        = $resource['id'];
				$data      = $resource['data'];
				$source    = $this->getByName($resource['backend-class']);
				$sourceRef = $resource['backend-ref'];

				if (!empty($source)) {
					$this->logger->info('Get data from old backend.', ['oldBackend' => $source, 'oldReference' => $sourceRef]);
					$data = $source->get($sourceRef);
				}

				$this->logger->info('Save data to new backend.', ['newBackend' => $destination]);
				$destinationRef = $destination->put($data);
				$this->logger->info('Saved data.', ['newReference' => $destinationRef]);

				if ($destinationRef !== '') {
					$this->logger->info('update row');
					if ($this->dba->update($table, ['backend-class' => $destination, 'backend-ref' => $destinationRef, 'data' => ''], ['id' => $id])) {
						if (!empty($source)) {
							$this->logger->info('Delete data from old backend.', ['oldBackend' => $source, 'oldReference' => $sourceRef]);
							$source->delete($sourceRef);
						}
						$moved++;
					}
				}
			}

			$this->dba->close($resources);
		}

		return $moved;
	}
}
