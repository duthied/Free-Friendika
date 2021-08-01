<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

use Exception;
use Friendica\Core\Config\IConfig;
use Friendica\Core\L10n;
use Friendica\Util\Strings;

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
class Filesystem implements ISelectableStorage
{
	const NAME = 'Filesystem';

	// Default base folder
	const DEFAULT_BASE_FOLDER = 'storage';

	/** @var IConfig */
	private $config;

	/** @var string */
	private $basePath;

	/** @var L10n */
	private $l10n;

	/**
	 * Filesystem constructor.
	 *
	 * @param IConfig         $config
	 * @param L10n            $l10n
	 */
	public function __construct(IConfig $config, L10n $l10n)
	{
		$this->config = $config;
		$this->l10n   = $l10n;

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
	private function pathForRef(string $reference): string
	{
		$fold1 = substr($reference, 0, 2);
		$fold2 = substr($reference, 2, 2);
		$file  = substr($reference, 4);

		return implode('/', [$this->basePath, $fold1, $fold2, $file]);
	}


	/**
	 * Create directory tree to store file, with .htaccess and index.html files
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
				throw new StorageException(sprintf('Filesystem storage failed to create "%s". Check you write permissions.', $path));
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
	public function get(string $reference): string
	{
		$file = $this->pathForRef($reference);
		if (!is_file($file)) {
			throw new ReferenceStorageException(sprintf('Filesystem storage failed to get the file %s, The file is invalid', $reference));
		}

		$result = file_get_contents($file);

		// just in case the result is REALLY false, not zero or empty or anything else, throw the exception
		if ($result === false) {
			throw new StorageException(sprintf('Filesystem storage failed to get data to "%s". Check your write permissions', $file));
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function put(string $data, string $reference = ''): string
	{
		if ($reference === '') {
			try {
				$reference = Strings::getRandomHex();
			} catch (Exception $exception) {
				throw new StorageException('Filesystem storage failed to generate a random hex', $exception->getCode(), $exception);
			}
		}
		$file = $this->pathForRef($reference);

		$this->createFoldersForFile($file);

		$result = file_put_contents($file, $data);

		// just in case the result is REALLY false, not zero or empty or anything else, throw the exception
		if ($result === false) {
			throw new StorageException(sprintf('Filesystem storage failed to save data to "%s". Check your write permissions', $file));
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
		if (!is_file($file)) {
			throw new ReferenceStorageException(sprintf('File with reference "%s" doesn\'t exist', $reference));
		}

		if (!unlink($file)) {
			throw new StorageException(sprintf('Cannot delete with file with reference "%s"', $reference));
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getOptions(): array
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
	public function saveOptions(array $data): array
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
	public static function getName(): string
	{
		return self::NAME;
	}

	public function __toString()
	{
		return self::getName();
	}
}
