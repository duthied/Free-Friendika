<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Model\Storage;

use Friendica\Core\Config\IConfig;
use Friendica\Core\L10n;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Filesystem based storage backend
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

	/** @var IConfig */
	private $config;

	/** @var string */
	private $basePath;

	/**
	 * Filesystem constructor.
	 *
	 * @param IConfig         $config
	 * @param LoggerInterface $logger
	 * @param L10n            $l10n
	 */
	public function __construct(IConfig $config, LoggerInterface $logger, L10n $l10n)
	{
		parent::__construct($l10n, $logger);

		$this->config = $config;

		$path           = $this->config->get('storage', 'filesystem_path', self::DEFAULT_BASE_FOLDER);
		$this->basePath = rtrim($path, '/');
	}

	/**
	 * Split data ref and return file path
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
	 * Create dirctory tree to store file, with .htaccess and index.html files
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

		$result = file_put_contents($file, $data);

		// just in case the result is REALLY false, not zero or empty or anything else, throw the exception
		if ($result === false) {
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
