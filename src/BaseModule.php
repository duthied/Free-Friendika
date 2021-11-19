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

use Friendica\App\Router;
use Friendica\Capabilities\ICanHandleRequests;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Model\User;
use Friendica\Module\HTTPException\PageNotFound;
use Friendica\Network\HTTPException\NoContentException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

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

	/** @var L10n */
	protected $l10n;

	public function __construct(L10n $l10n, array $parameters = [])
	{
		$this->parameters = $parameters;
		$this->l10n       = $l10n;
	}

	/**
	 * Wraps the L10n::t() function for Modules
	 *
	 * @see L10n::t()
	 */
	protected function t(string $s, ...$args): string
	{
		return $this->l10n->t($s, ...$args);
	}

	/**
	 * Wraps the L10n::tt() function for Modules
	 *
	 * @see L10n::tt()
	 */
	protected function tt(string $singular, string $plurarl, int $count): string
	{
		return $this->l10n->tt($singular, $plurarl, $count);
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
	public function put()
	{
	}

	/** Gets the name of the current class */
	public function getClassName(): string
	{
		return static::class;
	}

	public function run(App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, array $server, array $post)
	{
		/* The URL provided does not resolve to a valid module.
		 *
		 * On Dreamhost sites, quite often things go wrong for no apparent reason and they send us to '/internal_error.html'.
		 * We don't like doing this, but as it occasionally accounts for 10-20% or more of all site traffic -
		 * we are going to trap this and redirect back to the requested page. As long as you don't have a critical error on your page
		 * this will often succeed and eventually do the right thing.
		 *
		 * Otherwise we are going to emit a 404 not found.
		 */
		if (static::class === PageNotFound::class) {
			$queryString = $server['QUERY_STRING'];
			// Stupid browser tried to pre-fetch our Javascript img template. Don't log the event or return anything - just quietly exit.
			if (!empty($queryString) && preg_match('/{[0-9]}/', $queryString) !== 0) {
				exit();
			}

			if (!empty($queryString) && ($queryString === 'q=internal_error.html') && isset($dreamhost_error_hack)) {
				$logger->info('index.php: dreamhost_error_hack invoked.', ['Original URI' => $server['REQUEST_URI']]);
				$baseUrl->redirect($server['REQUEST_URI']);
			}

			$logger->debug('index.php: page not found.', ['request_uri' => $server['REQUEST_URI'], 'address' => $server['REMOTE_ADDR'], 'query' => $server['QUERY_STRING']]);
		}

		// @see https://github.com/tootsuite/mastodon/blob/c3aef491d66aec743a3a53e934a494f653745b61/config/initializers/cors.rb
		if (substr($_REQUEST['pagename'] ?? '', 0, 12) == '.well-known/') {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Headers: *');
			header('Access-Control-Allow-Methods: ' . Router::GET);
			header('Access-Control-Allow-Credentials: false');
		} elseif (substr($_REQUEST['pagename'] ?? '', 0, 8) == 'profile/') {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Headers: *');
			header('Access-Control-Allow-Methods: ' . Router::GET);
			header('Access-Control-Allow-Credentials: false');
		} elseif (substr($_REQUEST['pagename'] ?? '', 0, 4) == 'api/') {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Headers: *');
			header('Access-Control-Allow-Methods: ' . implode(',', Router::ALLOWED_METHODS));
			header('Access-Control-Allow-Credentials: false');
			header('Access-Control-Expose-Headers: Link');
		} elseif (substr($_REQUEST['pagename'] ?? '', 0, 11) == 'oauth/token') {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Headers: *');
			header('Access-Control-Allow-Methods: ' . Router::POST);
			header('Access-Control-Allow-Credentials: false');
		}

		// @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/OPTIONS
		// @todo Check allowed methods per requested path
		if ($server['REQUEST_METHOD'] === Router::OPTIONS) {
			header('Allow: ' . implode(',', Router::ALLOWED_METHODS));
			throw new NoContentException();
		}

		$placeholder = '';

		$profiler->set(microtime(true), 'ready');
		$timestamp = microtime(true);

		Core\Hook::callAll($args->getModuleName() . '_mod_init', $placeholder);

		$profiler->set(microtime(true) - $timestamp, 'init');

		if ($server['REQUEST_METHOD'] === Router::DELETE) {
			$this->delete();
		}

		if ($server['REQUEST_METHOD'] === Router::PATCH) {
			$this->patch();
		}

		if ($server['REQUEST_METHOD'] === Router::POST) {
			Core\Hook::callAll($args->getModuleName() . '_mod_post', $post);
			$this->post();
		}

		if ($server['REQUEST_METHOD'] === Router::PUT) {
			$this->put();
		}

		// "rawContent" is especially meant for technical endpoints.
		// This endpoint doesn't need any theme initialization or other comparable stuff.
		$this->rawContent();
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
