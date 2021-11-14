<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica;

use Friendica\Capabilities\ICanHandleRequests;
use Friendica\Core\Logger;
use Friendica\Model\User;

/**
 * All modules in Friendica should extend BaseModule, although not all modules
 * need to extend all the methods described here
 *
 * The filename of the module in src/Module needs to match the class name
 * exactly to make the module available.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
abstract class BaseModule implements ICanHandleRequests
{
	/** @var array */
	protected $parameters = [];

	public function __construct(array $parameters = [])
	{
		$this->parameters = $parameters;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init()
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function rawContent()
	{
		// echo '';
		// exit;
	}

	/**
	 * {@inheritDoc}
	 */
	public function content(): string
	{
		return '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete()
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function patch()
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function post()
	{
		// DI::baseurl()->redirect('module');
	}

	/**
	 * {@inheritDoc}
	 */
	public function afterpost()
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function put()
	{
	}

	/** Gets the name of the current class */
	public function getClassName(): string
	{
		return static::class;
	}

	/*
	 * Functions used to protect against Cross-Site Request Forgery
	 * The security token has to base on at least one value that an attacker can't know - here it's the session ID and the private key.
	 * In this implementation, a security token is reusable (if the user submits a form, goes back and resubmits the form, maybe with small changes;
	 * or if the security token is used for ajax-calls that happen several times), but only valid for a certain amount of time (3hours).
	 * The "typename" separates the security tokens of different types of forms. This could be relevant in the following case:
	 *    A security token is used to protect a link from CSRF (e.g. the "delete this profile"-link).
	 *    If the new page contains by any chance external elements, then the used security token is exposed by the referrer.
	 *    Actually, important actions should not be triggered by Links / GET-Requests at all, but sometimes they still are,
	 *    so this mechanism brings in some damage control (the attacker would be able to forge a request to a form of this type, but not to forms of other types).
	 */
	public static function getFormSecurityToken($typename = '')
	{
		$user = User::getById(DI::app()->getLoggedInUserId(), ['guid', 'prvkey']);
		$timestamp = time();
		$sec_hash = hash('whirlpool', ($user['guid'] ?? '') . ($user['prvkey'] ?? '') . session_id() . $timestamp . $typename);

		return $timestamp . '.' . $sec_hash;
	}

	public static function checkFormSecurityToken($typename = '', $formname = 'form_security_token')
	{
		$hash = null;

		if (!empty($_REQUEST[$formname])) {
			/// @TODO Careful, not secured!
			$hash = $_REQUEST[$formname];
		}

		if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
			/// @TODO Careful, not secured!
			$hash = $_SERVER['HTTP_X_CSRF_TOKEN'];
		}

		if (empty($hash)) {
			return false;
		}

		$max_livetime = 10800; // 3 hours

		$user = User::getById(DI::app()->getLoggedInUserId(), ['guid', 'prvkey']);

		$x = explode('.', $hash);
		if (time() > (intval($x[0]) + $max_livetime)) {
			return false;
		}

		$sec_hash = hash('whirlpool', ($user['guid'] ?? '') . ($user['prvkey'] ?? '') . session_id() . $x[0] . $typename);

		return ($sec_hash == $x[1]);
	}

	public static function getFormSecurityStandardErrorMessage()
	{
		return DI::l10n()->t("The form security token was not correct. This probably happened because the form has been opened for too long \x28>3 hours\x29 before submitting it.") . EOL;
	}

	public static function checkFormSecurityTokenRedirectOnError($err_redirect, $typename = '', $formname = 'form_security_token')
	{
		if (!self::checkFormSecurityToken($typename, $formname)) {
			Logger::notice('checkFormSecurityToken failed: user ' . DI::app()->getLoggedInUserNickname() . ' - form element ' . $typename);
			Logger::debug('checkFormSecurityToken failed', ['request' => $_REQUEST]);
			notice(self::getFormSecurityStandardErrorMessage());
			DI::baseUrl()->redirect($err_redirect);
		}
	}

	public static function checkFormSecurityTokenForbiddenOnError($typename = '', $formname = 'form_security_token')
	{
		if (!self::checkFormSecurityToken($typename, $formname)) {
			Logger::notice('checkFormSecurityToken failed: user ' . DI::app()->getLoggedInUserNickname() . ' - form element ' . $typename);
			Logger::debug('checkFormSecurityToken failed', ['request' => $_REQUEST]);

			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}
	}

	protected static function getContactFilterTabs(string $baseUrl, string $current, bool $displayCommonTab)
	{
		$tabs = [
			[
				'label' => DI::l10n()->t('All contacts'),
				'url'   => $baseUrl . '/contacts',
				'sel'   => !$current || $current == 'all' ? 'active' : '',
			],
			[
				'label' => DI::l10n()->t('Followers'),
				'url'   => $baseUrl . '/contacts/followers',
				'sel'   => $current == 'followers' ? 'active' : '',
			],
			[
				'label' => DI::l10n()->t('Following'),
				'url'   => $baseUrl . '/contacts/following',
				'sel'   => $current == 'following' ? 'active' : '',
			],
			[
				'label' => DI::l10n()->t('Mutual friends'),
				'url'   => $baseUrl . '/contacts/mutuals',
				'sel'   => $current == 'mutuals' ? 'active' : '',
			],
		];

		if ($displayCommonTab) {
			$tabs[] = [
				'label' => DI::l10n()->t('Common'),
				'url'   => $baseUrl . '/contacts/common',
				'sel'   => $current == 'common' ? 'active' : '',
			];
		}

		return $tabs;
	}
}
