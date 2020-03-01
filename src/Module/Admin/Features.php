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

namespace Friendica\Module\Admin;

use Friendica\Content\Feature;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;

class Features extends BaseAdmin
{
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		parent::checkFormSecurityTokenRedirectOnError('/admin/features', 'admin_manage_features');

		$features = Feature::get(false);

		foreach ($features as $fname => $fdata) {
			foreach (array_slice($fdata, 1) as $f) {
				$feature = $f[0];
				$feature_state = 'feature_' . $feature;
				$featurelock = 'featurelock_' . $feature;

				if (!empty($_POST[$feature_state])) {
					$val = intval($_POST[$feature_state]);
				} else {
					$val = 0;
				}
				DI::config()->set('feature', $feature, $val);

				if (!empty($_POST[$featurelock])) {
					DI::config()->set('feature_lock', $feature, $val);
				} else {
					DI::config()->delete('feature_lock', $feature);
				}
			}
		}

		DI::baseUrl()->redirect('admin/features');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$arr = [];
		$features = Feature::get(false);

		foreach ($features as $fname => $fdata) {
			$arr[$fname] = [];
			$arr[$fname][0] = $fdata[0];
			foreach (array_slice($fdata, 1) as $f) {
				$set = DI::config()->get('feature', $f[0], $f[3]);
				$arr[$fname][1][] = [
					['feature_' . $f[0], $f[1], $set, $f[2]],
					['featurelock_' . $f[0], DI::l10n()->t('Lock feature %s', $f[1]), $f[4], '']
				];
			}
		}

		$tpl = Renderer::getMarkupTemplate('admin/features.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$form_security_token' => parent::getFormSecurityToken("admin_manage_features"),
			'$title' => DI::l10n()->t('Manage Additional Features'),
			'$features' => $arr,
			'$submit' => DI::l10n()->t('Save Settings'),
		]);

		return $o;
	}
}
