<?php

namespace Friendica\Module\Contact;

use Friendica\BaseModule;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Util\XML;

class Poke extends BaseModule
{
	public static function post(array $parameters = [])
	{
		if (!local_user() || empty($parameters['id'])) {
			return self::postReturn(false);
		}

		$uid = local_user();

		if (empty($_POST['verb'])) {
			return self::postReturn(false);
		}

		$verb = $_POST['verb'];

		$verbs = DI::l10n()->getPokeVerbs();
		if (!array_key_exists($verb, $verbs)) {
			return self::postReturn(false);
		}

		$activity = Activity::POKE . '#' . urlencode($verbs[$verb][0]);

		$contact_id = intval($parameters['id']);
		if (!$contact_id) {
			return self::postReturn(false);
		}

		Logger::info('verb ' . $verb . ' contact ' . $contact_id);

		$contact = DBA::selectFirst('contact', ['id', 'name'], ['id' => $parameters['id'], 'uid' => local_user()]);
		if (!DBA::isResult($contact)) {
			return self::postReturn(false);
		}

		$a = DI::app();

		$private = (!empty($_GET['private']) ? intval($_GET['private']) : Model\Item::PUBLIC);

		$allow_cid     = ($private ? '<' . $contact['id']. '>' : $a->user['allow_cid']);
		$allow_gid     = ($private ? '' : $a->user['allow_gid']);
		$deny_cid      = ($private ? '' : $a->user['deny_cid']);
		$deny_gid      = ($private ? '' : $a->user['deny_gid']);

		$actor = $a->contact;

		$uri = Model\Item::newURI($uid);

		$arr = [];

		$arr['guid']          = System::createUUID();
		$arr['uid']           = $uid;
		$arr['uri']           = $uri;
		$arr['parent-uri']    = $uri;
		$arr['wall']          = 1;
		$arr['contact-id']    = $actor['id'];
		$arr['owner-name']    = $actor['name'];
		$arr['owner-link']    = $actor['url'];
		$arr['owner-avatar']  = $actor['thumb'];
		$arr['author-name']   = $actor['name'];
		$arr['author-link']   = $actor['url'];
		$arr['author-avatar'] = $actor['thumb'];
		$arr['title']         = '';
		$arr['allow_cid']     = $allow_cid;
		$arr['allow_gid']     = $allow_gid;
		$arr['deny_cid']      = $deny_cid;
		$arr['deny_gid']      = $deny_gid;
		$arr['visible']       = 1;
		$arr['verb']          = $activity;
		$arr['private']       = $private;
		$arr['object-type']   = Activity\ObjectType::PERSON;

		$arr['origin']        = 1;
		$arr['body']          = '[url=' . $actor['url'] . ']' . $actor['name'] . '[/url]' . ' ' . $verbs[$verb][2] . ' ' . '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';

		$arr['object'] = '<object><type>' . Activity\ObjectType::PERSON . '</type><title>' . XML::escape($contact['name']) . '</title><id>' . XML::escape($contact['url']) . '</id>';
		$arr['object'] .= '<link>' . XML::escape('<link rel="alternate" type="text/html" href="' . $contact['url'] . '" />') . "\n";

		$arr['object'] .= XML::escape('<link rel="photo" type="image/jpeg" href="' . $contact['photo'] . '" />') . "\n";
		$arr['object'] .= '</link></object>' . "\n";

		$result = Model\Item::insert($arr);

		Hook::callAll('post_local_end', $arr);

		return self::postReturn($result);
	}

	/**
	 * Since post() is called before rawContent(), we need to be able to return a JSON response in post() directly.
	 *
	 * @param bool $success
	 * @return bool
	 */
	private static function postReturn(bool $success)
	{
		if (!$success) {
			notice(DI::l10n()->t('Error while sending poke, please retry.'));
		}

		if (DI::mode()->isAjax()) {
			System::jsonExit(['success' => $success]);
		}

		return $success;
	}

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			throw new HTTPException\UnauthorizedException(DI::l10n()->t('You must be logged in to use this module.'));
		}

		if (empty($parameters['id'])) {
			throw new HTTPException\BadRequestException();
		}

		$contact = DBA::selectFirst('contact', ['id', 'url', 'name'], ['id' => $parameters['id'], 'uid' => local_user()]);
		if (!DBA::isResult($contact)) {
			throw new HTTPException\NotFoundException();
		}

		Model\Profile::load(DI::app(), '', Model\Contact::getByURL($contact["url"], false));

		$verbs = [];
		foreach (DI::l10n()->getPokeVerbs() as $verb => $translations) {
			if ($translations[1] !== 'NOTRANSLATION') {
				$verbs[$verb] = $translations[1];
			}
		}

		$tpl = Renderer::getMarkupTemplate('contact/poke.tpl');
		$o = Renderer::replaceMacros($tpl,[
			'$title'    => DI::l10n()->t('Poke/Prod'),
			'$desc'     => DI::l10n()->t('poke, prod or do other things to somebody'),
			'$id'       => $contact['id'],
			'$verb'     => ['verb', DI::l10n()->t('Choose what you wish to do to recipient'), '', '', $verbs],
			'$private'  => ['private', DI::l10n()->t('Make this post private')],
			'$loading'  => DI::l10n()->t('Loading...'),
			'$submit'   => DI::l10n()->t('Submit'),

		]);

		return $o;
	}
}
