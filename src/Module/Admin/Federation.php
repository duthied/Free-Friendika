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
		$platforms = ['Friendi%%a', 'Diaspora', '%%red%%', 'Hubzilla', 'BlaBlaNet', 'GNU Social', 'StatusNet', 'Mastodon', 'Pleroma', 'socialhome', 'ganggo'];
		$colors = [
			'Friendi%%a' => '#ffc018', // orange from the logo
			'Diaspora'   => '#a1a1a1', // logo is black and white, makes a gray
			'%%red%%'    => '#c50001', // fire red from the logo
			'Hubzilla'   => '#43488a', // blue from the logo
			'BlaBlaNet'  => '#3B5998', // blue from the navbar at blablanet-dot-com
			'GNU Social' => '#a22430', // dark red from the logo
			'StatusNet'  => '#789240', // the green from the logo (red and blue have already others
			'Mastodon'   => '#1a9df9', // blue from the Mastodon logo
			'Pleroma'    => '#E46F0F', // Orange from the text that is used on Pleroma instances
			'socialhome' => '#52056b', // lilac from the Django Image used at the Socialhome homepage
			'ganggo'     => '#69d7e2', // from the favicon
		];
		$counts = [];
		$total = 0;
		$users = 0;

		foreach ($platforms as $platform) {
			// get a total count for the platform, the name and version of the
			// highest version and the protocol tpe
			$platformCount = DBA::fetchFirst('SELECT
       			COUNT(*) AS `total`,
       			SUM(`registered-users`) AS `users`,
       			ANY_VALUE(`platform`) AS `platform`,
				ANY_VALUE(`network`) AS `network`,
       			MAX(`version`) AS `version` FROM `gserver`
				WHERE `platform` LIKE ?
			  	AND `last_contact` >= `last_failure`
				ORDER BY `version` ASC', $platform);
			$total += $platformCount['total'];
			$users += $platformCount['users'];

			// what versions for that platform do we know at all?
			// again only the active nodes
			$versionCountsStmt = DBA::p('SELECT
       			COUNT(*) AS `total`,
       			`version` FROM `gserver`
				WHERE `last_contact` >= `last_failure`
				AND `platform` LIKE ?
				GROUP BY `version`
				ORDER BY `version`;', $platform);
			$versionCounts = DBA::toArray($versionCountsStmt);

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

			// Reformat and compact version numbers
			if ($platform == 'Pleroma') {
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
			}

			// in the DB the Diaspora versions have the format x.x.x.x-xx the last
			// part (-xx) should be removed to clean up the versions from the "head
			// commit" information and combined into a single entry for x.x.x.x
			if ($platform == 'Diaspora') {
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
			}

			// early friendica versions have the format x.x.xxxx where xxxx is the
			// DB version stamp; those should be operated out and versions be
			// conbined
			if ($platform == 'Friendi%%a') {
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

			// the 3rd array item is needed for the JavaScript graphs as JS does
			// not like some characters in the names of variables...
			$counts[$platform] = [$platformCount, $versionCounts, str_replace([' ', '%'], '', $platform), $colors[$platform]];
		}

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
}
