<?php
/**
 * @file src/Model/Storage/Filesystem.php
 * @brief Storage backend system
 */

namespace Friendica\Model\Storage;

use Friendica\Core\Config\IConfiguration;
use Friendica\Core\L10n\L10n;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * @brief Filesystem based storage backend
 *
 * This class manage data on filesystem.
 * Base folder for storage is set in storage.filesystem_path.
 * Best would be for storage folder to be outside webserver folder, we are using a
 * folder relative to code tree root as default to ease things for users in shared hostings.
 * Each new resource gets a value as reference and is saved in a
 * folder tree stucture created from that value.
 */
class Filesystem extends AbstractStorage
{
	const NAME = 'Filesystem';

	// Default base folder
	const DEFAULT_BASE_FOLDER = 'storage';

	/** @var IConfiguration */
	private $config;

	/** @var string */
	private $basePath;

	/**
	 * Filesystem constructor.
	 *
	 * @param IConfiguration  $config
	 * @param LoggerInterface $logger
	 * @param L10n            $l10n
	 */
	public function __construct(IConfiguration $config, LoggerInterface $logger, L10n $l10n)
	{
		parent::__construct($l10n, $logger);

		$this->config = $config;

		$path           = $this->config->get('storage', 'filesystem_path', self::DEFAULT_BASE_FOLDER);
		$this->basePath = rtrim($path, '/');
	}

	/**
	 * @brief Split data ref and return file path
	 *
	 * @param string $reference Data reference
	 *
	 * @return string
	 */
	private function pathForRef(string $reference)
	{
		$fold1 = substr($reference, 0, 2);
		$fold2 = substr($reference, 2, 2);
		$file  = substr($reference, 4);

		return implode('/', [$this->basePath, $fold1, $fold2, $file]);
	}


	/**
	 * @brief Create dirctory tree to store file, with .htaccess and index.html files
	 *
	 * @param string $file Path and filename
	 *
	 * @throws StorageException
	 */
	private function createFoldersForFile(string $file)
	{
		$path = dirname($file);

		if (!is_dir($path)) {
			if (!mkdir($path, 0770, true)) {
				$this->logger->warning('Failed to create dir.', ['path' => $path]);
				throw new StorageException($this->l10n->t('Filesystem storage failed to create "%s". Check you write permissions.', $path));
			}
		}

		while ($path !== $this->basePath) {
			if (!is_file($path . '/index.html')) {
				file_put_contents($path . '/index.html', '');
			}
			chmod($path . '/index.html', 0660);
			chmod($path, 0770);
			$path = dirname($path);
		}
		if (!is_file($path . '/index.html')) {
			file_put_contents($path . '/index.html', '');
			chmod($path . '/index.html', 0660);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $reference)
	{
		$file = $this->pathForRef($reference);
		if (!is_file($file)) {
			return '';
		}

		return file_get_contents($file);
	}

	/**
	 * @inheritDoc
	 */
	public function put(string $data, string $reference = '')
	{
		if ($reference === '') {
			$reference = Strings::getRandomHex();
		}
		$file = $this->pathForRef($reference);

		$this->createFoldersForFile($file);

		if ((file_exists($file) && !is_writable($file)) || !file_put_contents($file, $data)) {
			$this->logger->warning('Failed to write data.', ['file' => $file]);
			throw new StorageException($this->l10n->t('Filesystem storage failed to save data to "%s". Check your write permissions', $file));
		}

		chmod($file, 0660);
		return $reference;
	}

	/**
	 * @inheritDoc
	 */
	public function delete(string $reference)
	{
		$file = $this->pathForRef($reference);
		// return true if file doesn't exists. we want to delete it: success with zero work!
		if (!is_file($file)) {
			return true;
		}
		return unlink($file);
	}

	/**
	 * @inheritDoc
	 */
	public function getOptions()
	{
		return [
			'storagepath' => [
				'input',
				$this->l10n->t('Storage base path'),
				$this->basePath,
				$this->l10n->t('Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree')
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function saveOptions(array $data)
	{
		$storagePath = $data['storagepath'] ?? '';
		if ($storagePath === '' || !is_dir($storagePath)) {
			return [
				'storagepath' => $this->l10n->t('Enter a valid existing folder')
			];
		};
		$this->config->set('storage', 'filesystem_path', $storagePath);
		$this->basePath = $storagePath;
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public static function getName()
	{
		return self::NAME;
	}
}
