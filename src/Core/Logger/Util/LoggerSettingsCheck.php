<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Core\Logger\Util;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Logger\Capability\ICheckLoggerSettings;
use Friendica\Core\Logger\Exception\LoggerUnusableException;

/** {@inheritDoc} */
class LoggerSettingsCheck implements ICheckLoggerSettings
{
	/** @var IManageConfigValues */
	protected $config;
	/** @var $fileSystem */
	protected $fileSystem;
	/** @var L10n */
	protected $l10n;

	public function __construct(IManageConfigValues $config, FileSystem $fileSystem, L10n $l10n)
	{
		$this->config     = $config;
		$this->fileSystem = $fileSystem;
		$this->l10n       = $l10n;
	}

	/** {@inheritDoc} */
	public function checkLogfile(): ?string
	{
		// Check logfile permission
		if ($this->config->get('system', 'debugging')) {
			$file = $this->config->get('system', 'logfile');

			try {
				$stream = $this->fileSystem->createStream($file);

				if (!isset($stream)) {
					throw new LoggerUnusableException('Stream is null.');
				}
			} catch (\Throwable $exception) {
				return $this->l10n->t('The logfile \'%s\' is not usable. No logging possible (error: \'%s\')', $file, $exception->getMessage());
			}
		}

		return null;
	}

	/** {@inheritDoc} */
	public function checkDebugLogfile(): ?string
	{
		// Check logfile permission
		if ($this->config->get('system', 'debugging')) {
			$file = $this->config->get('system', 'dlogfile');

			if (empty($file)) {
				return null;
			}

			try {
				$stream = $this->fileSystem->createStream($file);

				if (!isset($stream)) {
					throw new LoggerUnusableException('Stream is null.');
				}
			} catch (\Throwable $exception) {
				return $this->l10n->t('The debug logfile \'%s\' is not usable. No logging possible (error: \'%s\')', $file, $exception->getMessage());
			}
		}

		return null;
	}
}
