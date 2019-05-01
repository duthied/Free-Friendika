<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Model\User;
use Friendica\Protocol\Salmon;
use Friendica\Util\Strings;

/**
 * Prints responses to /.well-known/webfinger  or /xrd requests
 */
class Xrd extends BaseModule
{
	public static function rawContent()
	{
		$app = self::getApp();

		// @TODO: Replace with parameter from router
		if ($app->argv[0] == 'xrd') {
			if (empty($_GET['uri'])) {
				return;
			}

			$uri = urldecode(Strings::escapeTags(trim($_GET['uri'])));
			if (defaults($_SERVER, 'HTTP_ACCEPT', '') == 'application/jrd+json') {
				$mode = 'json';
			} else {
				$mode = 'xml';
			}
		} else {
			if (empty($_GET['resource'])) {
				return;
			}

			$uri = urldecode(Strings::escapeTags(trim($_GET['resource'])));
			if (defaults($_SERVER, 'HTTP_ACCEPT', '') == 'application/xrd+xml') {
				$mode = 'xml';
			} else {
				$mode = 'json';
			}
		}

		if (substr($uri, 0, 4) === 'http') {
			$name = ltrim(basename($uri), '~');
		} else {
			$local = str_replace('acct:', '', $uri);
			if (substr($local, 0, 2) == '//') {
				$local = substr($local, 2);
			}

			$name = substr($local, 0, strpos($local, '@'));
		}

		$user = User::getByNickname($name);

		if (empty($user)) {
			System::httpExit(404);
		}

		$profileURL = $app->getBaseURL() . '/profile/' . $user['nickname'];
		$alias = str_replace('/profile/', '/~', $profileURL);

		$addr = 'acct:' . $user['nickname'] . '@' . $app->getHostName();
		if ($app->getURLPath()) {
			$addr .= '/' . $app->getURLPath();
		}

		if ($mode == 'xml') {
			self::printXML($addr, $alias, $profileURL, $app->getBaseURL(), $user);
		} else {
			self::printJSON($addr, $alias, $profileURL, $app->getBaseURL(), $user);
		}
	}

	private static function printJSON($uri, $alias, $orofileURL, $baseURL, $user)
	{
		$salmon_key = Salmon::salmonKey($user['spubkey']);

		header('Access-Control-Allow-Origin: *');
		header('Content-type: application/json; charset=utf-8');

		$json = [
			'subject' => $uri,
			'aliases' => [
				$alias,
				$orofileURL,
			],
			'links'   => [
				[
					'rel'  => NAMESPACE_DFRN,
					'href' => $orofileURL,
				],
				[
					'rel'  => NAMESPACE_FEED,
					'type' => 'application/atom+xml',
					'href' => $baseURL . '/dfrn_poll/' . $user['nickname'],
				],
				[
					'rel'  => 'http://webfinger.net/rel/profile-page',
					'type' => 'text/html',
					'href' => $orofileURL,
				],
				[
					'rel'  => 'self',
					'type' => 'application/activity+json',
					'href' => $orofileURL,
				],
				[
					'rel'  => 'http://microformats.org/profile/hcard',
					'type' => 'text/html',
					'href' => $baseURL . '/hcard/' . $user['nickname'],
				],
				[
					'rel'  => NAMESPACE_POCO,
					'href' => $baseURL . '/poco/' . $user['nickname'],
				],
				[
					'rel'  => 'http://webfinger.net/rel/avatar',
					'type' => 'image/jpeg',
					'href' => $baseURL . '/photo/profile/' . $user['uid'] . '.jpg',
				],
				[
					'rel'  => 'http://joindiaspora.com/seed_location',
					'type' => 'text/html',
					'href' => $baseURL,
				],
				[
					'rel'  => 'salmon',
					'href' => $baseURL . '/salmon/' . $user['nickname'],
				],
				[
					'rel'  => 'http://salmon-protocol.org/ns/salmon-replies',
					'href' => $baseURL . '/salmon/' . $user['nickname'],
				],
				[
					'rel'  => 'http://salmon-protocol.org/ns/salmon-mention',
					'href' => $baseURL . '/salmon/' . $user['nickname'] . '/mention',
				],
				[
					'rel'      => 'http://ostatus.org/schema/1.0/subscribe',
					'template' => $baseURL . '/follow?url={uri}',
				],
				[
					'rel'  => 'magic-public-key',
					'href' => 'data:application/magic-public-key,' . $salmon_key,
				],
				[
					'rel'  => 'http://purl.org/openwebauth/v1',
					'type' => 'application/x-zot+json',
					'href' => $baseURL . '/owa',
				],
			],
		];

		echo json_encode($json);
		exit();
	}

	private static function printXML($uri, $alias, $profileURL, $baseURL, $user)
	{
		$salmon_key = Salmon::salmonKey($user['spubkey']);

		header('Access-Control-Allow-Origin: *');
		header('Content-type: text/xml');

		$tpl = Renderer::getMarkupTemplate('xrd_person.tpl');

		$o = Renderer::replaceMacros($tpl, [
			'$nick'        => $user['nickname'],
			'$accturi'     => $uri,
			'$alias'       => $alias,
			'$profile_url' => $profileURL,
			'$hcard_url'   => $baseURL . '/hcard/' . $user['nickname'],
			'$atom'        => $baseURL . '/dfrn_poll/' . $user['nickname'],
			'$poco_url'    => $baseURL . '/poco/' . $user['nickname'],
			'$photo'       => $baseURL . '/photo/profile/' . $user['uid'] . '.jpg',
			'$baseurl'     => $baseURL,
			'$salmon'      => $baseURL . '/salmon/' . $user['nickname'],
			'$salmen'      => $baseURL . '/salmon/' . $user['nickname'] . '/mention',
			'$subscribe'   => $baseURL . '/follow?url={uri}',
			'$openwebauth' => $baseURL . '/owa',
			'$modexp'      => 'data:application/magic-public-key,' . $salmon_key
		]);

		$arr = ['user' => $user, 'xml' => $o];
		Hook::callAll('personal_xrd', $arr);

		echo $arr['xml'];
		exit();
	}
}
