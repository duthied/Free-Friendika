<?php
/**
 * @file mod/fsuggest.php
 */

use Friendica\App;
use Friendica\Core\ACL;
use Friendica\Core\L10n;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Worker\Delivery;

function fsuggest_post(App $a)
{
	if (!local_user()) {
		return;
	}

	if ($a->argc != 2) {
		return;
	}

	$contact_id = intval($a->argv[1]);
	if (empty($contact_id)) {
		return;
	}

	// We do query the "uid" as well to ensure that it is our contact
	if (!DBA::exists('contact', ['id' => $contact_id, 'uid' => local_user()])) {
		notice(L10n::t('Contact not found.') . EOL);
		return;
	}

	$suggest_contact_id = intval($_POST['suggest']);
	if (empty($suggest_contact_id)) {
		return;
	}

	// We do query the "uid" as well to ensure that it is our contact
	$contact = DBA::selectFirst('contact', ['name', 'url', 'request', 'avatar'], ['id' => $suggest_contact_id, 'uid' => local_user()]);
	if (!DBA::isResult($contact)) {
		notice(L10n::t('Suggested contact not found.') . EOL);
		return;
	}

	$note = Strings::escapeHtml(trim($_POST['note'] ?? ''));

	$fields = ['uid' => local_user(),'cid' => $contact_id, 'name' => $contact['name'],
		'url' => $contact['url'], 'request' => $contact['request'],
		'photo' => $contact['avatar'], 'note' => $note, 'created' => DateTimeFormat::utcNow()];
	DBA::insert('fsuggest', $fields);

	Worker::add(PRIORITY_HIGH, 'Notifier', Delivery::SUGGESTION, DBA::lastInsertId());

	info(L10n::t('Friend suggestion sent.') . EOL);
}

function fsuggest_content(App $a)
{
	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	if ($a->argc != 2) {
		return;
	}

	$contact_id = intval($a->argv[1]);

	$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => local_user()]);
	if (! DBA::isResult($contact)) {
		notice(L10n::t('Contact not found.') . EOL);
		return;
	}

	$o = '<h3>' . L10n::t('Suggest Friends') . '</h3>';

	$o .= '<div id="fsuggest-desc" >' . L10n::t('Suggest a friend for %s', $contact['name']) . '</div>';

	$o .= '<form id="fsuggest-form" action="fsuggest/' . $contact_id . '" method="post" >';

	$o .= ACL::getSuggestContactSelectHTML(
		'suggest',
		'suggest-select',
		['size' => 4, 'exclude' => $contact_id, 'networks' => 'DFRN_ONLY', 'single' => true]
	);


	$o .= '<div id="fsuggest-submit-wrapper"><input id="fsuggest-submit" type="submit" name="submit" value="' . L10n::t('Submit') . '" /></div>';
	$o .= '</form>';

	return $o;
}
