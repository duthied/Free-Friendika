<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Theme;

/**
 * Prints theme specific details as a JSON string
 */
class ThemeDetails extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		if (!empty($_REQUEST['theme'])) {
			$theme = $_REQUEST['theme'];
			$info = Theme::getInfo($theme);

			// Unfortunately there will be no translation for this string
			$description = $info['description'] ?? '';
			$version     = $info['version']     ?? '';
			$credits     = $info['credits']     ?? '';

			echo json_encode([
				'img'     => Theme::getScreenshot($theme),
				'desc'    => $description,
				'version' => $version,
				'credits' => $credits,
			]);
		}
		exit();
	}
}
