<?php

namespace Friendica\Module\Admin;

use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Module\BaseAdminModule;

class Federation extends BaseAdminModule
{
	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		// get counts on active friendica, diaspora, redmatrix, hubzilla, gnu
		// social and statusnet nodes this node is knowing
		//
		// We are looking for the following platforms in the DB, "Red" should find
		// all variants of that platform ID string as the q() function is stripping
		// off one % two of them are needed in the query
		// Add more platforms if you like, when one returns 0 known nodes it is not
		// displayed on the stats page.
		$systems = [
			'Friendica'   => ['name' => 'Friendica', 'color' => '#ffc018'], // orange from the logo
			'diaspora'    => ['name' => 'Diaspora', 'color' => '#a1a1a1'], // logo is black and white, makes a gray
			'red'         => ['name' => 'Red Matrix', 'color' => '#c50001'], // fire red from the logo
			'hubzilla'    => ['name' => 'Hubzilla', 'color' => '#43488a'], // blue from the logo
			'gnusocial'   => ['name' => 'GNU Social', 'color' => '#a22430'], // dark red from the logo
			'statusnet'   => ['name' => 'StatusNet', 'color' => '#789240'], // the green from the logo (red and blue have already others
			'mastodon'    => ['name' => 'Mastodon', 'color' => '#1a9df9'], // blue from the Mastodon logo
			'pleroma'     => ['name' => 'Pleroma', 'color' => '#E46F0F'], // Orange from the text that is used on Pleroma instances
			'socialhome'  => ['name' => 'SocialHome', 'color' => '#52056b'], // lilac from the Django Image used at the Socialhome homepage
			'wordpress'   => ['name' => 'WordPress', 'color' => '#016087'], // Background color of the homepage
			'misskey'     => ['name' => 'Misskey', 'color' => '#ccfefd'], // Font color of the homepage
			'funkwhale'   => ['name' => 'Funkwhale', 'color' => '#4082B4'], // From the homepage
			'plume'       => ['name' => 'Plume', 'color' => '#7765e3'], // From the homepage
			'pixelfed'    => ['name' => 'Pixelfed', 'color' => '#11da47'], // One of the logo colors
			'peertube'    => ['name' => 'Peertube', 'color' => '#ffad5c'], // One of the logo colors
			'writefreely' => ['name' => 'WriteFreely', 'color' => '#292929'], // Font color of the homepage
			'other'       => ['name' => L10n::t('Other'), 'color' => '#F1007E'], // ActivityPub main color
		];

		$platforms = array_keys($systems);

		$counts = [];
		$total = 0;
		$users = 0;

		$gservers = DBA::p("SELECT COUNT(*) AS `total`, SUM(`registered-users`) AS `users`, `platform`,
			ANY_VALUE(`network`) AS `network`, MAX(`version`) AS `version`
			FROM `gserver` WHERE `last_contact` >= `last_failure` GROUP BY `platform`");
		while ($gserver = DBA::fetch($gservers)) {
			$total += $gserver['total'];
			$users += $gserver['users'];

			$versionCounts = [];
			$versions = DBA::p("SELECT COUNT(*) AS `total`, `version` FROM `gserver`
				WHERE `last_contact` >= `last_failure` AND `platform` = ?
				GROUP BY `version` ORDER BY `version`", $gserver['platform']);
			while ($version = DBA::fetch($versions)) {
				$version['version'] = str_replace(["\n", "\r", "\t"], " ", $version['version']);
				$versionCounts[] = $version;
			}
			DBA::close($versions);

			$platform = $gserver['platform'];

			if ($platform == 'Friendika') {
				$platform = 'Friendica';
			} elseif (in_array($platform, ['Red Matrix', 'redmatrix'])) {
				$platform = 'red';
			} elseif(stristr($platform, 'pleroma')) {
				$platform = 'pleroma';
			} elseif(stristr($platform, 'wordpress')) {
				$platform = 'wordpress';
			} elseif (!in_array($platform, $platforms)) {
				$platform = 'other';
			}

			if ($platform != $gserver['platform']) {
				if ($platform == 'other') {
					$versionCounts = $counts[$platform][1] ?? [];
					$versionCounts[] = ['version' => $gserver['platform'] ?: L10n::t('unknown'), 'total' => $gserver['total']];
					$gserver['version'] = '';
				} else {
					$versionCounts = array_merge($versionCounts, $counts[$platform][1] ?? []);
				}

				$gserver['platform'] = $platform;
				$gserver['total'] += $counts[$platform][0]['total'] ?? 0;
				$gserver['users'] += $counts[$platform][0]['users'] ?? 0;
			}

			if ($platform == 'Friendica') {
				$versionCounts = self::reformaFriendicaVersions($versionCounts);
			} elseif ($platform == 'pleroma') {
				$versionCounts = self::reformaPleromaVersions($versionCounts);
			} elseif ($platform == 'diaspora') {
				$versionCounts = self::reformaDiasporaVersions($versionCounts);
			}

			$versionCounts = self::sortVersion($versionCounts);

			$gserver['platform'] = $systems[$platform]['name'];

			$counts[$platform] = [$gserver, $versionCounts, str_replace([' ', '%'], '', $platform), $systems[$platform]['color']];
		}
		DBA::close($gserver);

		// some helpful text
		$intro = L10n::t('This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.');
		$hint = L10n::t('The <em>Auto Discovered Contact Directory</em> feature is not enabled, it will improve the data displayed here.');

		// load the template, replace the macros and return the page content
		$t = Renderer::getMarkupTemplate('admin/federation.tpl');
		return Renderer::replaceMacros($t, [
			'$title' => L10n::t('Administration'),
			'$page' => L10n::t('Federation Statistics'),
			'$intro' => $intro,
			'$hint' => $hint,
			'$autoactive' => Config::get('system', 'poco_completion'),
			'$counts' => $counts,
			'$version' => FRIENDICA_VERSION,
			'$legendtext' => L10n::t('Currently this node is aware of %d nodes with %d registered users from the following platforms:', $total, $users),
		]);
	}

	// early friendica versions have the format x.x.xxxx where xxxx is the
	// DB version stamp; those should be operated out and versions be
	// conbined
	private static function reformaFriendicaVersions($versionCounts)
	{
		$newV = [];
		$newVv = [];
		foreach ($versionCounts as $vv) {
			$newVC = $vv['total'];
			$newVV = $vv['version'];
			$lastDot = strrpos($newVV, '.');
			$len = strlen($newVV) - 1;
			if (($lastDot == $len - 4) && (!strrpos($newVV, '-rc') == $len - 3)) {
				$newVV = substr($newVV, 0, $lastDot);
			}
			if (isset($newV[$newVV])) {
				$newV[$newVV] += $newVC;
			} else {
				$newV[$newVV] = $newVC;
			}
		}
		foreach ($newV as $key => $value) {
			array_push($newVv, ['total' => $value, 'version' => $key]);
		}
		$versionCounts = $newVv;

		return $versionCounts;
	}

	// in the DB the Diaspora versions have the format x.x.x.x-xx the last
	// part (-xx) should be removed to clean up the versions from the "head
	// commit" information and combined into a single entry for x.x.x.x
	private static function reformaDiasporaVersions($versionCounts)
	{
		$newV = [];
		$newVv = [];
		foreach ($versionCounts as $vv) {
			$newVC = $vv['total'];
			$newVV = $vv['version'];
			$posDash = strpos($newVV, '-');
			if ($posDash) {
				$newVV = substr($newVV, 0, $posDash);
			}
			if (isset($newV[$newVV])) {
				$newV[$newVV] += $newVC;
			} else {
				$newV[$newVV] = $newVC;
			}
		}
		foreach ($newV as $key => $value) {
			array_push($newVv, ['total' => $value, 'version' => $key]);
		}
		$versionCounts = $newVv;

		return $versionCounts;
	}

	private static function reformaPleromaVersions($versionCounts)
	{
		$compacted = [];
		foreach ($versionCounts as $key => $value) {
			$version = $versionCounts[$key]['version'];
			$parts = explode(' ', trim($version));
			do {
				$part = array_pop($parts);
			} while (!empty($parts) && ((strlen($part) >= 40) || (strlen($part) <= 3)));
			// only take the x.x.x part of the version, not the "release" after the dash
			if (!empty($part) && strpos($part, '-')) {
				$part = explode('-', $part)[0];
			}
			if (!empty($part)) {
				if (empty($compacted[$part])) {
					$compacted[$part] = $versionCounts[$key]['total'];
				} else {
					$compacted[$part] += $versionCounts[$key]['total'];
				}
			}
		}

		$versionCounts = [];
		foreach ($compacted as $version => $pl_total) {
			$versionCounts[] = ['version' => $version, 'total' => $pl_total];
		}

		return $versionCounts;
	}

	// Reformat and compact version numbers
	private static function sortVersion($versionCounts)
	{
		//
		// clean up version numbers
		//
		// some platforms do not provide version information, add a unkown there
		// to the version string for the displayed list.
		foreach ($versionCounts as $key => $value) {
			if ($versionCounts[$key]['version'] == '') {
				$versionCounts[$key] = ['total' => $versionCounts[$key]['total'], 'version' => L10n::t('unknown')];
			}
		}

		// Assure that the versions are sorted correctly
		$v2 = [];
		$versions = [];
		foreach ($versionCounts as $vv) {
			$version = trim(strip_tags($vv["version"]));
			$v2[$version] = $vv;
			$versions[] = $version;
		}

		usort($versions, 'version_compare');

		$versionCounts = [];
		foreach ($versions as $version) {
			$versionCounts[] = $v2[$version];
		}

		return $versionCounts;
	}
}
