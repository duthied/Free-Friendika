<?php

namespace Friendica\Module\Contact;

use Friendica\BaseModule;
use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module;
use Friendica\Network\HTTPException;

class Contacts extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$app = DI::app();

		if (!local_user()) {
			throw new HTTPException\ForbiddenException();
		}

		$cid = $parameters['id'];
		$type = $parameters['type'] ?? 'all';

		if (!$cid) {
			throw new HTTPException\BadRequestException(DI::l10n()->t('Invalid contact.'));
		}

		$contact = Model\Contact::getById($cid, []);
		if (empty($contact)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('Contact not found.'));
		}

		$localContactId = Model\Contact::getPublicIdByUserId(local_user());

		Model\Profile::load($app, '', $contact);

		$condition = [
			'blocked' => false,
			'self' => false,
			'hidden' => false,
		];

		$noresult_label = DI::l10n()->t('No known contacts.');

		switch ($type) {
			case 'followers':
				$total = Model\Contact\Relation::countFollowers($cid, $condition);
				break;
			case 'following':
				$total = Model\Contact\Relation::countFollows($cid, $condition);
				break;
			case 'mutuals':
				$total = Model\Contact\Relation::countMutuals($cid, $condition);
				break;
			case 'common':
				$condition = [
					'NOT `self` AND NOT `blocked` AND NOT `hidden` AND `id` != ?',
					$localContactId,
				];
				$total = Model\Contact\Relation::countCommon($localContactId, $cid, $condition);
				$noresult_label = DI::l10n()->t('No common contacts.');
				break;
			default:
				$total = Model\Contact\Relation::countAll($cid, $condition);
		}

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString());
		$desc = '';

		switch ($type) {
			case 'followers':
				$friends = Model\Contact\Relation::listFollowers($cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = DI::l10n()->tt('Follower (%s)', 'Followers (%s)', $total);
				break;
			case 'following':
				$friends = Model\Contact\Relation::listFollows($cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = DI::l10n()->tt('Following (%s)', 'Following (%s)', $total);
				break;
			case 'mutuals':
				$friends = Model\Contact\Relation::listMutuals($cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = DI::l10n()->tt('Mutual friend (%s)', 'Mutual friends (%s)', $total);
				$desc = DI::l10n()->t(
					'These contacts both follow and are followed by <strong>%s</strong>.',
					htmlentities($contact['name'], ENT_COMPAT, 'UTF-8')
				);
				break;
			case 'common':
				$friends = Model\Contact\Relation::listCommon($localContactId, $cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = DI::l10n()->tt('Common contact (%s)', 'Common contacts (%s)', $total);
				$desc = DI::l10n()->t(
					'Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).',
					htmlentities($contact['name'], ENT_COMPAT, 'UTF-8')
				);
				break;
			default:
				$friends = Model\Contact\Relation::listAll($cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = DI::l10n()->tt('Contact (%s)', 'Contacts (%s)', $total);
		}

		$o = Module\Contact::getTabsHTML($contact, Module\Contact::TAB_CONTACTS);

		$tabs = self::getContactFilterTabs('contact/' . $cid, $type, true);

		$contacts = array_map([Module\Contact::class, 'getContactTemplateVars'], $friends);

		$tpl = Renderer::getMarkupTemplate('profile/contacts.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$title'    => $title,
			'$desc'     => $desc,
			'$tabs'     => $tabs,

			'$noresult_label'  => $noresult_label,

			'$contacts' => $contacts,
			'$paginate' => $pager->renderFull($total),
		]);

		return $o;
	}
}
