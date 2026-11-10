<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use Friendica\Core\L10n;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * This mock module enable class encapsulation of legacy global function modules.
 * After having provided the module file name, all the methods will behave like a normal Module class.
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class LegacyModule extends BaseModule
{
	/**
	 * The module name, which is the name of the file (without the .php suffix)
	 * It's used to check for existence of the module functions.
	 *
	 * @var string
	 */
	private $moduleName = '';

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, string $file_path = '', array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->setModuleFile($file_path);

		$this->runModuleFunction('init');
	}

	/**
	 * The only method that needs to be called, with the module/addon file name.
	 *
	 * @param string $file_path
	 * @throws \Exception
	 */
	private function setModuleFile(string $file_path)
	{
		if (!is_readable($file_path)) {
			throw new \Exception(DI::l10n()->t('Legacy module file not found: %s', $file_path));
		}

		$this->moduleName = basename($file_path, '.php');

		require_once $file_path;
	}

	protected function content(array $request = []): string
	{
		return $this->runModuleFunction('content');
	}

	protected function post(array $request = [])
	{
		parent::post($request);

		$this->runModuleFunction('post');
	}

	/**
	 * Runs the module function designated by the provided suffix if it exists, the BaseModule method otherwise
	 *
	 * @param string $function_suffix
	 * @return string
	 * @throws \Exception
	 */
	private function runModuleFunction(string $function_suffix): string
	{
		$function_name = $this->moduleName . '_' . $function_suffix;

		if (\function_exists($function_name)) {
			return $function_name() ?? '';
		}

		return '';
	}
}
