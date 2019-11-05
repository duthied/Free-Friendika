<?php

namespace Friendica\Module\Contact;

use Friendica\BaseModule;
use Friendica\Core\Config;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;
use Friendica\Util\Proxy;

/**
 * Asynchronous HTML fragment provider for frio contact hovercards
 */
class Hovercard extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$contact_url = $_REQUEST['url'] ?? '';

		// Get out if the system doesn't have public access allowed
		if (Config::get('system', 'block_public') && !Session::isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		// If a contact is connected the url is internally changed to 'redir/CID'. We need the pure url to search for
		// the contact. So we strip out the contact id from the internal url and look in the contact table for
		// the real url (nurl)
		if (strpos($contact_url, 'redir/') === 0) {
			$cid = intval(substr($contact_url, 6));
			$remote_contact = Contact::selectFirst(['nurl'], ['id' => $cid]);
			$contact_url = $remote_contact['nurl'] ?? '';
		}

		$contact = [];

		// if it's the url containing https it should be converted to http
		$contact_nurl = Strings::normaliseLink(GContact::cleanContactUrl($contact_url));
		if (!$contact_nurl) {
			throw new HTTPException\BadRequestException();
		}

		// Search for contact data
		// Look if the local user has got the contact
		if (Session::isAuthenticated()) {
			$contact = Contact::getDetailsByURL($contact_nurl, local_user());
		}

		// If not then check the global user
		if (!count($contact)) {
			$contact = Contact::getDetailsByURL($contact_nurl);
		}

		// Feeds url could have been destroyed through "cleanContactUrl", so we now use the original url
		if (!count($contact) && Session::isAuthenticated()) {
			$contact_nurl = Strings::normaliseLink($contact_url);
			$contact = Contact::getDetailsByURL($contact_nurl, local_user());
		}

		if (!count($contact)) {
			$contact_nurl = Strings::normaliseLink($contact_url);
			$contact = Contact::getDetailsByURL($contact_nurl);
		}

		if (!count($contact)) {
			throw new HTTPException\NotFoundException();
		}

		// Get the photo_menu - the menu if possible contact actions
		if (Session::isAuthenticated()) {
			$actions = Contact::photoMenu($contact);
		} else {
			$actions = [];
		}

		// Move the contact data to the profile array so we can deliver it to
		$tpl = Renderer::getMarkupTemplate('hovercard.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$profile' => [
				'name'         => $contact['name'],
				'nick'         => $contact['nick'],
				'addr'         => $contact['addr'] ?: $contact['url'],
				'thumb'        => Proxy::proxifyUrl($contact['thumb'], false, Proxy::SIZE_THUMB),
				'url'          => Contact::magicLink($contact['url']),
				'nurl'         => $contact['nurl'],
				'location'     => $contact['location'],
				'gender'       => $contact['gender'],
				'about'        => $contact['about'],
				'network_link' => Strings::formatNetworkName($contact['network'], $contact['url']),
				'tags'         => $contact['keywords'],
				'bd'           => $contact['birthday'] <= DBA::NULL_DATE ? '' : $contact['birthday'],
				'account_type' => Contact::getAccountType($contact),
				'actions'      => $actions,
			],
		]);

		echo $o;
		exit();
	}
}
