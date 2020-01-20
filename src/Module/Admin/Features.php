<?php

namespace Friendica\Module\Admin;

use Friendica\Content\Feature;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdminModule;

class Features extends BaseAdminModule
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
					['feature_' . $f[0], $f[1], $set, $f[2], [DI::l10n()->t('Off'), DI::l10n()->t('On')]],
					['featurelock_' . $f[0], DI::l10n()->t('Lock feature %s', $f[1]), (($f[4] !== false) ? "1" : ''), '', [DI::l10n()->t('Off'), DI::l10n()->t('On')]]
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
