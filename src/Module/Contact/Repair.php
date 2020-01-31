<?php

namespace Friendica\Module\Contact;

use Friendica\BaseModule;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module\Contact;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Strings;

/**
 * GUI for repairing contact details
 */
class Repair extends BaseModule
{
	public static function init(array $parameters = [])
	{
		if (!Session::isAuthenticated()) {
			throw new ForbiddenException(DI::l10n()->t('Permission denied.'));
		}
	}

	public static function post(array $parameters = [])
	{
		$cid = $parameters['contact'];

		$contact = Model\Contact::selectFirst([], ['id' => $cid, 'uid' => local_user()]);
		if (empty($contact)) {
			throw new BadRequestException(DI::l10n()->t('Contact not found.'));
		}

		$name        = ($_POST['name'] ?? '') ?: $contact['name'];
		$nick        = $_POST['nick'] ?? '';
		$url         = $_POST['url'] ?? '';
		$alias       = $_POST['alias'] ?? '';
		$request     = $_POST['request'] ?? '';
		$confirm     = $_POST['confirm'] ?? '';
		$notify      = $_POST['notify'] ?? '';
		$poll        = $_POST['poll'] ?? '';
		$attag       = $_POST['attag'] ?? '';
		$photo       = $_POST['photo'] ?? '';
		$remote_self = $_POST['remote_self'] ?? false;
		$nurl        = Strings::normaliseLink($url);

		$r = DI::dba()->update(
			'contact',
			[
				'name'        => $name,
				'nick'        => $nick,
				'url'         => $url,
				'nurl'        => $nurl,
				'alias'       => $alias,
				'request'     => $request,
				'confirm'     => $confirm,
				'notify'      => $notify,
				'poll'        => $poll,
				'attag'       => $attag,
				'remote_self' => $remote_self,
			],
			['id' => $contact['id'], 'uid' => local_user()]
		);

		if ($photo) {
			DI::logger()->notice('Updating photo.', ['photo' => $photo]);

			Model\Contact::updateAvatar($photo, local_user(), $contact['id']);
		}

		if ($r) {
			info(DI::l10n()->t('Contact settings applied.') . EOL);
		} else {
			notice(DI::l10n()->t('Contact update failed.') . EOL);
		}

		return;
	}

	public static function content(array $parameters = [])
	{
		$cid = $parameters['contact'];

		$cid = $parameters['contact'];

		$contact = Model\Contact::selectFirst([], ['id' => $cid, 'uid' => local_user()]);
		if (empty($contact)) {
			throw new BadRequestException(DI::l10n()->t('Contact not found.'));
		}

		if (empty(DI::page()['aside'])) {
			DI::page()['aside'] = '';
		}

		$a = DI::app();

		$a->data['contact'] = $contact;
		Model\Profile::load($a, "", Model\Contact::getDetailsByURL($contact["url"]));

		$warning = DI::l10n()->t('<strong>WARNING: This is highly advanced</strong> and if you enter incorrect information your communications with this contact may stop working.');
		$info    = DI::l10n()->t('Please use your browser \'Back\' button <strong>now</strong> if you are uncertain what to do on this page.');

		$returnaddr = "contact/$cid";

		$allow_remote_self = DI::config()->get('system', 'allow_users_remote_self');

		// Disable remote self for everything except feeds.
		// There is an issue when you repeat an item from maybe twitter and you got comments from friendica and twitter
		// Problem is, you couldn't reply to both networks.
		if (!in_array($contact['network'], [Protocol::FEED, Protocol::DFRN, Protocol::DIASPORA, Protocol::TWITTER])) {
			$allow_remote_self = false;
		}

		if ($contact['network'] == Protocol::FEED) {
			$remote_self_options = ['0' => DI::l10n()->t('No mirroring'), '1' => DI::l10n()->t('Mirror as forwarded posting'), '2' => DI::l10n()->t('Mirror as my own posting')];
		} else {
			$remote_self_options = ['0' => DI::l10n()->t('No mirroring'), '2' => DI::l10n()->t('Mirror as my own posting')];
		}

		$update_profile = in_array($contact['network'], Protocol::FEDERATED);

		$tab_str = Contact::getTabsHTML($a, $contact, 6);

		$tpl = Renderer::getMarkupTemplate('crepair.tpl');
		return Renderer::replaceMacros($tpl, [
			'$tab_str'           => $tab_str,
			'$warning'           => $warning,
			'$info'              => $info,
			'$returnaddr'        => $returnaddr,
			'$return'            => DI::l10n()->t('Return to contact editor'),
			'$update_profile'    => $update_profile,
			'$udprofilenow'      => DI::l10n()->t('Refetch contact data'),
			'$contact_id'        => $contact['id'],
			'$lbl_submit'        => DI::l10n()->t('Submit'),
			'$label_remote_self' => DI::l10n()->t('Remote Self'),
			'$allow_remote_self' => $allow_remote_self,
			'$remote_self'       => ['remote_self',
				DI::l10n()->t('Mirror postings from this contact'),
				$contact['remote_self'],
				DI::l10n()->t('Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'),
				$remote_self_options
			],

			'$name'    => ['name', DI::l10n()->t('Name'), $contact['name']],
			'$nick'    => ['nick', DI::l10n()->t('Account Nickname'), $contact['nick']],
			'$attag'   => ['attag', DI::l10n()->t('@Tagname - overrides Name/Nickname'), $contact['attag']],
			'$url'     => ['url', DI::l10n()->t('Account URL'), $contact['url']],
			'$alias'   => ['alias', DI::l10n()->t('Account URL Alias'), $contact['alias']],
			'$request' => ['request', DI::l10n()->t('Friend Request URL'), $contact['request']],
			'confirm'  => ['confirm', DI::l10n()->t('Friend Confirm URL'), $contact['confirm']],
			'notify'   => ['notify', DI::l10n()->t('Notification Endpoint URL'), $contact['notify']],
			'poll'     => ['poll', DI::l10n()->t('Poll/Feed URL'), $contact['poll']],
			'photo'    => ['photo', DI::l10n()->t('New photo from this URL'), ''],
		]);
	}
}
