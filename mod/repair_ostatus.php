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

use Friendica\App;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;

function repair_ostatus_content(App $a) {

	if (! local_user()) {
		notice(DI::l10n()->t('Permission denied.') . EOL);
		DI::baseUrl()->redirect('ostatus_repair');
		// NOTREACHED
	}

	$o = "<h2>".DI::l10n()->t("Resubscribing to OStatus contacts")."</h2>";

	$uid = local_user();

	$counter = intval($_REQUEST['counter']);

	$r = q("SELECT COUNT(*) AS `total` FROM `contact` WHERE
	`uid` = %d AND `network` = '%s' AND `rel` IN (%d, %d)",
		intval($uid),
		DBA::escape(Protocol::OSTATUS),
		intval(Contact::FRIEND),
		intval(Contact::SHARING));

	if (!DBA::isResult($r)) {
		return ($o . DI::l10n()->t("Error"));
	}

	$total = $r[0]["total"];

	$r = q("SELECT `url` FROM `contact` WHERE
		`uid` = %d AND `network` = '%s' AND `rel` IN (%d, %d)
		ORDER BY `url`
		LIMIT %d, 1",
		intval($uid),
		DBA::escape(Protocol::OSTATUS),
		intval(Contact::FRIEND),
		intval(Contact::SHARING), $counter++);

	if (!DBA::isResult($r)) {
		$o .= DI::l10n()->t("Done");
		return $o;
	}

	$o .= "<p>".$counter."/".$total.": ".$r[0]["url"]."</p>";

	$o .= "<p>".DI::l10n()->t("Keep this window open until done.")."</p>";

	Contact::createFromProbe($uid, $r[0]["url"], true);

	DI::page()['htmlhead'] = '<meta http-equiv="refresh" content="1; URL=' . DI::baseUrl() . '/repair_ostatus?counter='.$counter.'">';

	return $o;
}
