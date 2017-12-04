<?php
/**
 * @file src/Protocol/OAuth1.php
 */
namespace Friendica\Protocol;

use Friendica\App;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Protocol\FKOAuthDataStore;
use dba;

require_once "library/OAuth1.php";
require_once "include/plugin.php";

/**
 * @brief OAuth protocol
 */
class FKOAuth1 extends OAuthServer
{
	/**
	 * @brief Constructor
	 */
	public function __construct()
	{
		parent::__construct(new FKOAuthDataStore());
		$this->add_signature_method(new OAuthSignatureMethod_PLAINTEXT());
		$this->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
	}

	function loginUser($uid)
	{
		logger("FKOAuth1::loginUser $uid");
		$a = get_app();
		$r = q("SELECT * FROM `user` WHERE uid=%d AND `blocked` = 0 AND `account_expired` = 0 AND `account_removed` = 0 AND `verified` = 1 LIMIT 1",
			intval($uid)
		);
		if (DBM::is_result($r)){
			$record = $r[0];
		} else {
		   logger('FKOAuth1::loginUser failure: ' . print_r($_SERVER,true), LOGGER_DEBUG);
		    header('HTTP/1.0 401 Unauthorized');
		    die('This api requires login');
		}
		$_SESSION['uid'] = $record['uid'];
		$_SESSION['theme'] = $record['theme'];
		$_SESSION['mobile-theme'] = PConfig::get($record['uid'], 'system', 'mobile_theme');
		$_SESSION['authenticated'] = 1;
		$_SESSION['page_flags'] = $record['page-flags'];
		$_SESSION['my_url'] = System::baseUrl() . '/profile/' . $record['nickname'];
		$_SESSION['addr'] = $_SERVER['REMOTE_ADDR'];
		$_SESSION["allow_api"] = true;

		//notice( t("Welcome back ") . $record['username'] . EOL);
		$a->user = $record;

		if (strlen($a->user['timezone'])) {
			date_default_timezone_set($a->user['timezone']);
			$a->timezone = $a->user['timezone'];
		}

		$r = q("SELECT * FROM `contact` WHERE `uid` = %s AND `self` = 1 LIMIT 1",
			intval($_SESSION['uid']));
		if (DBM::is_result($r)) {
			$a->contact = $r[0];
			$a->cid = $r[0]['id'];
			$_SESSION['cid'] = $a->cid;
		}
		q("UPDATE `user` SET `login_date` = '%s' WHERE `uid` = %d",
			dbesc(datetime_convert()),
			intval($_SESSION['uid'])
		);

		call_hooks('logged_in', $a->user);
	}
}
