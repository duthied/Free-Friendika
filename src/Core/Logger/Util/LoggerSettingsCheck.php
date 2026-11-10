<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	/** @var FileSystem */
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
			} catch (LoggerUnusableException $exception) {
				throw new LoggerUnusableException('Stream is null.', $exception);
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

			if ($file === null || $file === '') {
				return null;
			}

			try {
				$stream = $this->fileSystem->createStream($file);
			} catch (LoggerUnusableException $exception) {
				throw new LoggerUnusableException('Stream is null.', $exception);
			} catch (\Throwable $exception) {
				return $this->l10n->t('The debug logfile \'%s\' is not usable. No logging possible (error: \'%s\')', $file, $exception->getMessage());
			}
		}

		return null;
	}
}
