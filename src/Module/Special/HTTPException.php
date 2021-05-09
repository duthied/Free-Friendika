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

namespace Friendica\Module\Special;

use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\DI;

/**
 * This special module displays HTTPException when they are thrown in modules.
 *
 * @package Friendica\Module\Special
 */
class HTTPException
{
	/**
	 * Generates the necessary template variables from the caught HTTPException.
	 *
	 * Fills in the blanks if title or descriptions aren't provided by the exception.
	 *
	 * @param \Friendica\Network\HTTPException $e
	 * @return array ['$title' => ..., '$description' => ...]
	 */
	private static function getVars(\Friendica\Network\HTTPException $e)
	{
		$message = $e->getMessage();

		$titles = [
			200 => 'OK',
			400 => DI::l10n()->t('Bad Request'),
			401 => DI::l10n()->t('Unauthorized'),
			403 => DI::l10n()->t('Forbidden'),
			404 => DI::l10n()->t('Not Found'),
			500 => DI::l10n()->t('Internal Server Error'),
			503 => DI::l10n()->t('Service Unavailable'),
		];
		$title = ($titles[$e->getCode()] ?? '') ?: 'Error ' . $e->getCode();

		if (empty($message)) {
			// Explanations are taken from https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
			$explanation = [
				400 => DI::l10n()->t('The server cannot or will not process the request due to an apparent client error.'),
				401 => DI::l10n()->t('Authentication is required and has failed or has not yet been provided.'),
				403 => DI::l10n()->t('The request was valid, but the server is refusing action. The user might not have the necessary permissions for a resource, or may need an account.'),
				404 => DI::l10n()->t('The requested resource could not be found but may be available in the future.'),
				500 => DI::l10n()->t('An unexpected condition was encountered and no more specific message is suitable.'),
				503 => DI::l10n()->t('The server is currently unavailable (because it is overloaded or down for maintenance). Please try again later.'),
			];

			$message = $explanation[$e->getCode()] ?? '';
		}

		$vars = [
			'$title' => $title,
			'$message' => $message,
			'$back' => DI::l10n()->t('Go back'),
			'$stack_trace' => DI::l10n()->t('Stack trace:'),
		];

		if (is_site_admin()) {
			$vars['$thrown'] = DI::l10n()->t('Exception thrown in %s:%d', $e->getFile(), $e->getLine());
			$vars['$trace'] = $e->getTraceAsString();
		}

		return $vars;
	}

	/**
	 * Displays a bare message page with no theming at all.
	 *
	 * @param \Friendica\Network\HTTPException $e
	 * @throws \Exception
	 */
	public static function rawContent(\Friendica\Network\HTTPException $e)
	{
		$content = '';

		if ($e->getCode() >= 400) {
			$tpl = Renderer::getMarkupTemplate('http_status.tpl');
			$content = Renderer::replaceMacros($tpl, self::getVars($e));
		}

		System::httpExit($e->getCode(), $e->httpdesc, $content);
	}

	/**
	 * Returns a content string that can be integrated in the current theme.
	 *
	 * @param \Friendica\Network\HTTPException $e
	 * @return string
	 * @throws \Exception
	 */
	public static function content(\Friendica\Network\HTTPException $e)
	{
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . $e->getCode() . ' ' . $e->httpdesc);

		$tpl = Renderer::getMarkupTemplate('exception.tpl');

		return Renderer::replaceMacros($tpl, self::getVars($e));
	}
}
