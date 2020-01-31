<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\Contact as ContactModel;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Worker\Delivery;

/**
 * Suggest friends to a known contact
 */
class FriendSuggest extends BaseModule
{
	public static function init(array $parameters = [])
	{
		if (! local_user()) {
			throw new ForbiddenException(DI::l10n()->t('Permission denied.'));
		}
	}

	public static function post(array $parameters = [])
	{
		$cid = intval($parameters['contact']);

		// We do query the "uid" as well to ensure that it is our contact
		if (!DI::dba()->exists('contact', ['id' => $cid, 'uid' => local_user()])) {
			throw new NotFoundException(DI::l10n()->t('Contact not found.'));
		}

		$suggest_contact_id = intval($_POST['suggest']);
		if (empty($suggest_contact_id)) {
			return;
		}

		// We do query the "uid" as well to ensure that it is our contact
		$contact = DI::dba()->selectFirst('contact', ['name', 'url', 'request', 'avatar'], ['id' => $suggest_contact_id, 'uid' => local_user()]);
		if (empty($contact)) {
			notice(DI::l10n()->t('Suggested contact not found.'));
			return;
		}

		$note = Strings::escapeHtml(trim($_POST['note'] ?? ''));

		$suggest = DI::fsuggest()->insert([
			'uid' => local_user(),
			'cid' => $cid,
			'name' => $contact['name'],
			'url' => $contact['url'],
			'request' => $contact['request'],
			'photo' => $contact['avatar'],
			'note' => $note,
			'created' => DateTimeFormat::utcNow()
		]);

		Worker::add(PRIORITY_HIGH, 'Notifier', Delivery::SUGGESTION, $suggest->id);

		info(DI::l10n()->t('Friend suggestion sent.'));
	}

	public static function content(array $parameters = [])
	{
		$cid = intval($parameters['contact']);

		$contact = DI::dba()->selectFirst('contact', [], ['id' => $cid, 'uid' => local_user()]);
		if (empty($contact)) {
			notice(DI::l10n()->t('Contact not found.'));
			DI::baseUrl()->redirect();
		}

		$stmtContacts = ContactModel::select(['id', 'name'], [
			'`uid` = ? AND NOT `self` AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND NOT `deleted` AND `notify` != "" AND `id` != ? AND `networks` = ?',
			local_user(),
			$cid,
			Protocol::DFRN,
		]);

		$formattedContacts = [];

		while ($contact = DI::dba()->fetch($stmtContacts)) {
			$formattedContacts[$contact['id']] = $contact['name'];
		}

		$tpl = Renderer::getMarkupTemplate('fsuggest.tpl');
		return Renderer::replaceMacros($tpl, [
			'$contact_id' => $cid,
			'$fsuggest_title' => DI::l10n()->t('Suggest Friends'),
			'$fsuggest_select' => [
				'suggest',
				DI::l10n()->t('Suggest a friend for %s', $contact['name']),
				'',
				'',
				$formattedContacts,
			],
			'$submit' => DI::l10n()->t('Submit'),
		]);
	}
}
