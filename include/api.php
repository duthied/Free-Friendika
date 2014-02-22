<?php
/* To-Do:
 - Automatically detect if incoming data is HTML or BBCode
*/
	require_once("include/bbcode.php");
	require_once("include/datetime.php");
	require_once("include/conversation.php");
	require_once("include/oauth.php");
	require_once("include/html2plain.php");
	/*
	 * Twitter-Like API
	 *
	 */

	$API = Array();
	$called_api = Null;

        function api_user() {
          // It is not sufficient to use local_user() to check whether someone is allowed to use the API,
          // because this will open CSRF holes (just embed an image with src=friendicasite.com/api/statuses/update?status=CSRF
          // into a page, and visitors will post something without noticing it).
          // Instead, use this function.
          if ($_SESSION["allow_api"])
            return local_user();

          return false;
        }

	function api_date($str){
		//Wed May 23 06:01:13 +0000 2007
		return datetime_convert('UTC', 'UTC', $str, "D M d H:i:s +0000 Y" );
	}


	function api_register_func($path, $func, $auth=false){
		global $API;
		$API[$path] = array('func'=>$func, 'auth'=>$auth);

		// Workaround for hotot
		$path = str_replace("api/", "api/1.1/", $path);
		$API[$path] = array('func'=>$func, 'auth'=>$auth);
	}

	/**
	 * Simple HTTP Login
	 */

	function api_login(&$a){
		// login with oauth
		try{
			$oauth = new FKOAuth1();
			list($consumer,$token) = $oauth->verify_request(OAuthRequest::from_request());
			if (!is_null($token)){
				$oauth->loginUser($token->uid);
				call_hooks('logged_in', $a->user);
				return;
			}
			echo __file__.__line__.__function__."<pre>"; var_dump($consumer, $token); die();
		}catch(Exception $e){
			logger(__file__.__line__.__function__."\n".$e);
			//die(__file__.__line__.__function__."<pre>".$e); die();
		}



		// workaround for HTTP-auth in CGI mode
		if(x($_SERVER,'REDIRECT_REMOTE_USER')) {
		 	$userpass = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"],6)) ;
			if(strlen($userpass)) {
			 	list($name, $password) = explode(':', $userpass);
				$_SERVER['PHP_AUTH_USER'] = $name;
				$_SERVER['PHP_AUTH_PW'] = $password;
			}
		}

		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			logger('API_login: ' . print_r($_SERVER,true), LOGGER_DEBUG);
			header('WWW-Authenticate: Basic realm="Friendica"');
			header('HTTP/1.0 401 Unauthorized');
			die((api_error($a, 'json', "This api requires login")));

			//die('This api requires login');
		}

		$user = $_SERVER['PHP_AUTH_USER'];
		$encrypted = hash('whirlpool',trim($_SERVER['PHP_AUTH_PW']));


		/**
		 *  next code from mod/auth.php. needs better solution
		 */

		// process normal login request

		$r = q("SELECT * FROM `user` WHERE ( `email` = '%s' OR `nickname` = '%s' )
			AND `password` = '%s' AND `blocked` = 0 AND `account_expired` = 0 AND `account_removed` = 0 AND `verified` = 1 LIMIT 1",
			dbesc(trim($user)),
			dbesc(trim($user)),
			dbesc($encrypted)
		);
		if(count($r)){
			$record = $r[0];
		} else {
		   logger('API_login failure: ' . print_r($_SERVER,true), LOGGER_DEBUG);
		    header('WWW-Authenticate: Basic realm="Friendica"');
		    header('HTTP/1.0 401 Unauthorized');
		    die('This api requires login');
		}

		require_once('include/security.php');
		authenticate_success($record); $_SESSION["allow_api"] = true;

		call_hooks('logged_in', $a->user);

	}

	/**************************
	 *  MAIN API ENTRY POINT  *
	 **************************/
	function api_call(&$a){
		GLOBAL $API, $called_api;

		// preset
		$type="json";

		foreach ($API as $p=>$info){
			if (strpos($a->query_string, $p)===0){
				$called_api= explode("/",$p);
				//unset($_SERVER['PHP_AUTH_USER']);
				if ($info['auth']===true && api_user()===false) {
						api_login($a);
				}

				load_contact_links(api_user());

				logger('API call for ' . $a->user['username'] . ': ' . $a->query_string);
				logger('API parameters: ' . print_r($_REQUEST,true));
				$type="json";
				if (strpos($a->query_string, ".xml")>0) $type="xml";
				if (strpos($a->query_string, ".json")>0) $type="json";
				if (strpos($a->query_string, ".rss")>0) $type="rss";
				if (strpos($a->query_string, ".atom")>0) $type="atom";
				if (strpos($a->query_string, ".as")>0) $type="as";

				$r = call_user_func($info['func'], $a, $type);
				if ($r===false) return;

				switch($type){
					case "xml":
						$r = mb_convert_encoding($r, "UTF-8",mb_detect_encoding($r));
						header ("Content-Type: text/xml");
						return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$r;
						break;
					case "json":
						header ("Content-Type: application/json");
						foreach($r as $rr)
						    return json_encode($rr);
						break;
					case "rss":
						header ("Content-Type: application/rss+xml");
						return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$r;
						break;
					case "atom":
						header ("Content-Type: application/atom+xml");
						return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$r;
						break;
					case "as":
						//header ("Content-Type: application/json");
						//foreach($r as $rr)
						//    return json_encode($rr);
						return json_encode($r);
						break;

				}
				//echo "<pre>"; var_dump($r); die();
			}
		}
		header("HTTP/1.1 404 Not Found");
		logger('API call not implemented: '.$a->query_string." - ".print_r($_REQUEST,true));
		return(api_error($a, $type, "not implemented"));

	}

	function api_error(&$a, $type, $error) {
		$r = "<status><error>".$error."</error><request>".$a->query_string."</request></status>";
		switch($type){
			case "xml":
				header ("Content-Type: text/xml");
				return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$r;
				break;
			case "json":
				header ("Content-Type: application/json");
				return json_encode(array('error' => $error, 'request' => $a->query_string));
				break;
			case "rss":
				header ("Content-Type: application/rss+xml");
				return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$r;
				break;
			case "atom":
				header ("Content-Type: application/atom+xml");
				return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$r;
				break;
		}
	}

	/**
	 * RSS extra info
	 */
	function api_rss_extra(&$a, $arr, $user_info){
		if (is_null($user_info)) $user_info = api_get_user($a);
		$arr['$user'] = $user_info;
		$arr['$rss'] = array(
			'alternate' => $user_info['url'],
			'self' => $a->get_baseurl(). "/". $a->query_string,
			'base' => $a->get_baseurl(),
			'updated' => api_date(null),
			'atom_updated' => datetime_convert('UTC','UTC','now',ATOM_TIME),
			'language' => $user_info['language'],
			'logo'	=> $a->get_baseurl()."/images/friendica-32.png",
		);

		return $arr;
	}


	/**
	 * Unique contact to contact url.
	 */
	function api_unique_id_to_url($id){
		$r = q("SELECT url FROM unique_contacts WHERE id=%d LIMIT 1",
			intval($id));
		if ($r)
			return ($r[0]["url"]);
		else
			return false;
	}

	/**
	 * Returns user info array.
	 */
	function api_get_user(&$a, $contact_id = Null, $type = "json"){
		global $called_api;
		$user = null;
		$extra_query = "";
		$url = "";
		$nick = "";

		logger("api_get_user: Fetching user data for user ".$contact_id, LOGGER_DEBUG);

		// Searching for contact URL
		if(!is_null($contact_id) AND (intval($contact_id) == 0)){
			$user = dbesc(normalise_link($contact_id));
			$url = $user;
			$extra_query = "AND `contact`.`nurl` = '%s' ";
			if (api_user()!==false)  $extra_query .= "AND `contact`.`uid`=".intval(api_user());
		}

		// Searching for unique contact id
		if(!is_null($contact_id) AND (intval($contact_id) != 0)){
			$user = dbesc(api_unique_id_to_url($contact_id));

			if ($user == "")
				die(api_error($a, $type, t("User not found.")));

			$url = $user;
			$extra_query = "AND `contact`.`nurl` = '%s' ";
			if (api_user()!==false)  $extra_query .= "AND `contact`.`uid`=".intval(api_user());
		}

		if(is_null($user) && x($_GET, 'user_id')) {
			$user = dbesc(api_unique_id_to_url($_GET['user_id']));

			if ($user == "")
				die(api_error($a, $type, t("User not found.")));

			$url = $user;
			$extra_query = "AND `contact`.`nurl` = '%s' ";
			if (api_user()!==false)  $extra_query .= "AND `contact`.`uid`=".intval(api_user());
		}
		if(is_null($user) && x($_GET, 'screen_name')) {
			$user = dbesc($_GET['screen_name']);
			$nick = $user;
			$extra_query = "AND `contact`.`nick` = '%s' ";
			if (api_user()!==false)  $extra_query .= "AND `contact`.`uid`=".intval(api_user());
		}

		if (is_null($user) AND ($a->argc > (count($called_api)-1)) AND (count($called_api) > 0)){
			$argid = count($called_api);
			list($user, $null) = explode(".",$a->argv[$argid]);
			if(is_numeric($user)){
				$user = dbesc(api_unique_id_to_url($user));

				if ($user == "")
					return false;

				$url = $user;
				$extra_query = "AND `contact`.`nurl` = '%s' ";
				if (api_user()!==false)  $extra_query .= "AND `contact`.`uid`=".intval(api_user());
			} else {
				$user = dbesc($user);
				$nick = $user;
				$extra_query = "AND `contact`.`nick` = '%s' ";
				if (api_user()!==false)  $extra_query .= "AND `contact`.`uid`=".intval(api_user());
			}
		}

		logger("api_get_user: user ".$user, LOGGER_DEBUG);

		if (!$user) {
			if (api_user()===false) {
				api_login($a); return False;
			} else {
				$user = $_SESSION['uid'];
				$extra_query = "AND `contact`.`uid` = %d AND `contact`.`self` = 1 ";
			}

		}

		logger('api_user: ' . $extra_query . ', user: ' . $user);
		// user info
		$uinfo = q("SELECT *, `contact`.`id` as `cid` FROM `contact`
				WHERE 1
				$extra_query",
				$user
		);

		// Selecting the id by priority, friendica first
		api_best_nickname($uinfo);

		// if the contact wasn't found, fetch it from the unique contacts
		if (count($uinfo)==0) {
			$r = array();

			if ($url != "")
				$r = q("SELECT * FROM unique_contacts WHERE url='%s' LIMIT 1", $url);
			elseif ($nick != "")
				$r = q("SELECT * FROM unique_contacts WHERE nick='%s' LIMIT 1", $nick);

			if ($r) {
				// If no nick where given, extract it from the address
				if (($r[0]['nick'] == "") OR ($r[0]['name'] == $r[0]['nick']))
					$r[0]['nick'] = api_get_nick($r[0]["url"]);

				$ret = array(
					'id' => $r[0]["id"],
					'id_str' => (string) $r[0]["id"],
					'name' => $r[0]["name"],
					'screen_name' => (($r[0]['nick']) ? $r[0]['nick'] : $r[0]['name']),
					'location' => NULL,
					'description' => NULL,
					'profile_image_url' => $r[0]["avatar"],
					'profile_image_url_https' => $r[0]["avatar"],
					'url' => $r[0]["url"],
					'protected' => false,
					'followers_count' => 0,
					'friends_count' => 0,
					'created_at' => api_date(0),
					'favourites_count' => 0,
					'utc_offset' => 0,
					'time_zone' => 'UTC',
					'statuses_count' => 0,
					'following' => false,
					'verified' => false,
					'statusnet_blocking' => false,
					'notifications' => false,
					'statusnet_profile_url' => $r[0]["url"],
					'uid' => 0,
					'cid' => 0,
					'self' => 0,
					'network' => '',
				);

				return $ret;
			} else
				die(api_error($a, $type, t("User not found.")));

		}

		if($uinfo[0]['self']) {
			$usr = q("select * from user where uid = %d limit 1",
				intval(api_user())
			);
			$profile = q("select * from profile where uid = %d and `is-default` = 1 limit 1",
				intval(api_user())
			);

			// count public wall messages
			$r = q("SELECT COUNT(`id`) as `count` FROM `item`
					WHERE  `uid` = %d
					AND `type`='wall'
					AND `allow_cid`='' AND `allow_gid`='' AND `deny_cid`='' AND `deny_gid`=''",
					intval($uinfo[0]['uid'])
			);
			$countitms = $r[0]['count'];
		}
		else {
			$r = q("SELECT COUNT(`id`) as `count` FROM `item`
					WHERE  `contact-id` = %d
					AND `allow_cid`='' AND `allow_gid`='' AND `deny_cid`='' AND `deny_gid`=''",
					intval($uinfo[0]['id'])
			);
			$countitms = $r[0]['count'];
		}

		// count friends
		$r = q("SELECT COUNT(`id`) as `count` FROM `contact`
				WHERE  `uid` = %d AND `rel` IN ( %d, %d )
				AND `self`=0 AND `blocked`=0 AND `pending`=0 AND `hidden`=0",
				intval($uinfo[0]['uid']),
				intval(CONTACT_IS_SHARING),
				intval(CONTACT_IS_FRIEND)
		);
		$countfriends = $r[0]['count'];

		$r = q("SELECT COUNT(`id`) as `count` FROM `contact`
				WHERE  `uid` = %d AND `rel` IN ( %d, %d )
				AND `self`=0 AND `blocked`=0 AND `pending`=0 AND `hidden`=0",
				intval($uinfo[0]['uid']),
				intval(CONTACT_IS_FOLLOWER),
				intval(CONTACT_IS_FRIEND)
		);
		$countfollowers = $r[0]['count'];

		$r = q("SELECT count(`id`) as `count` FROM item where starred = 1 and uid = %d and deleted = 0",
			intval($uinfo[0]['uid'])
		);
		$starred = $r[0]['count'];


		if(! $uinfo[0]['self']) {
			$countfriends = 0;
			$countfollowers = 0;
			$starred = 0;
		}

		// Add a nick if it isn't present there
		if (($uinfo[0]['nick'] == "") OR ($uinfo[0]['name'] == $uinfo[0]['nick'])) {
			$uinfo[0]['nick'] = api_get_nick($uinfo[0]["url"]);
			//if ($uinfo[0]['nick'] != "")
			//	q("UPDATE contact SET nick = '%s' WHERE id = %d",
			//		dbesc($uinfo[0]['nick']), intval($uinfo[0]["id"]));
		}

		// Fetching unique id
		$r = q("SELECT id FROM unique_contacts WHERE url='%s' LIMIT 1", dbesc(normalise_link($uinfo[0]['url'])));

		// If not there, then add it
		if (count($r) == 0) {
			q("INSERT INTO unique_contacts (url, name, nick, avatar) VALUES ('%s', '%s', '%s', '%s')",
				dbesc(normalise_link($uinfo[0]['url'])), dbesc($uinfo[0]['name']),dbesc($uinfo[0]['nick']), dbesc($uinfo[0]['micro']));

			$r = q("SELECT id FROM unique_contacts WHERE url='%s' LIMIT 1", dbesc(normalise_link($uinfo[0]['url'])));
		}

		require_once('include/contact_selectors.php');
		$network_name = network_to_name($uinfo[0]['network']);

		$ret = Array(
			'id' => intval($r[0]['id']),
			'id_str' => (string) intval($r[0]['id']),
			'name' => (($uinfo[0]['name']) ? $uinfo[0]['name'] : $uinfo[0]['nick']),
			'screen_name' => (($uinfo[0]['nick']) ? $uinfo[0]['nick'] : $uinfo[0]['name']),
			'location' => ($usr) ? $usr[0]['default-location'] : $network_name,
			'description' => (($profile) ? $profile[0]['pdesc'] : NULL),
			'profile_image_url' => $uinfo[0]['micro'],
			'profile_image_url_https' => $uinfo[0]['micro'],
			'url' => $uinfo[0]['url'],
			'protected' => false,
			'followers_count' => intval($countfollowers),
			'friends_count' => intval($countfriends),
			'created_at' => api_date($uinfo[0]['created']),
			'favourites_count' => intval($starred),
			'utc_offset' => "0",
			'time_zone' => 'UTC',
			'statuses_count' => intval($countitms),
			'following' => (($uinfo[0]['rel'] == CONTACT_IS_FOLLOWER) OR ($uinfo[0]['rel'] == CONTACT_IS_FRIEND)),
			'verified' => true,
			'statusnet_blocking' => false,
			'notifications' => false,
			'statusnet_profile_url' => $a->get_baseurl()."/contacts/".$uinfo[0]['cid'],
			'uid' => intval($uinfo[0]['uid']),
			'cid' => intval($uinfo[0]['cid']),
			'self' => $uinfo[0]['self'],
			'network' => $uinfo[0]['network'],
		);

		return $ret;

	}

	function api_item_get_user(&$a, $item) {

		$author = q("SELECT * FROM unique_contacts WHERE url='%s' LIMIT 1",
			dbesc(normalise_link($item['author-link'])));

		if (count($author) == 0) {
			q("INSERT INTO unique_contacts (url, name, avatar) VALUES ('%s', '%s', '%s')",
			dbesc(normalise_link($item["author-link"])), dbesc($item["author-name"]), dbesc($item["author-avatar"]));

			$author = q("SELECT id FROM unique_contacts WHERE url='%s' LIMIT 1",
				dbesc(normalise_link($item['author-link'])));
		} else if ($item["author-link"].$item["author-name"] != $author[0]["url"].$author[0]["name"]) {
			q("UPDATE unique_contacts SET name = '%s', avatar = '%s' WHERE url = '%s'",
			dbesc($item["author-name"]), dbesc($item["author-avatar"]), dbesc(normalise_link($item["author-link"])));
		}

		$owner = q("SELECT id FROM unique_contacts WHERE url='%s' LIMIT 1",
			dbesc(normalise_link($item['owner-link'])));

		if (count($owner) == 0) {
			q("INSERT INTO unique_contacts (url, name, avatar) VALUES ('%s', '%s', '%s')",
			dbesc(normalise_link($item["owner-link"])), dbesc($item["owner-name"]), dbesc($item["owner-avatar"]));

			$owner = q("SELECT id FROM unique_contacts WHERE url='%s' LIMIT 1",
				dbesc(normalise_link($item['owner-link'])));
		} else if ($item["owner-link"].$item["owner-name"] != $owner[0]["url"].$owner[0]["name"]) {
			q("UPDATE unique_contacts SET name = '%s', avatar = '%s' WHERE url = '%s'",
			dbesc($item["owner-name"]), dbesc($item["owner-avatar"]), dbesc(normalise_link($item["owner-link"])));
		}

		// Comments in threads may appear as wall-to-wall postings.
		// So only take the owner at the top posting.
		if ($item["id"] == $item["parent"])
			$status_user = api_get_user($a,$item["owner-link"]);
		else
			$status_user = api_get_user($a,$item["author-link"]);

		$status_user["protected"] = (($item["allow_cid"] != "") OR
						($item["allow_gid"] != "") OR
						($item["deny_cid"] != "") OR
						($item["deny_gid"] != ""));

		return ($status_user);
	}


	/**
	 *  load api $templatename for $type and replace $data array
	 */
	function api_apply_template($templatename, $type, $data){

		$a = get_app();

		switch($type){
			case "atom":
			case "rss":
			case "xml":
				$data = array_xmlify($data);
				$tpl = get_markup_template("api_".$templatename."_".$type.".tpl");
				if(! $tpl) {
					header ("Content-Type: text/xml");
					echo '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<status><error>not implemented</error></status>';
					killme();
				}
				$ret = replace_macros($tpl, $data);
				break;
			case "json":
				$ret = $data;
				break;
		}

		return $ret;
	}

	/**
	 ** TWITTER API
	 */

	/**
	 * Returns an HTTP 200 OK response code and a representation of the requesting user if authentication was successful;
	 * returns a 401 status code and an error message if not.
	 * http://developer.twitter.com/doc/get/account/verify_credentials
	 */
	function api_account_verify_credentials(&$a, $type){
		if (api_user()===false) return false;

		unset($_REQUEST["user_id"]);
		unset($_GET["user_id"]);

		unset($_REQUEST["screen_name"]);
		unset($_GET["screen_name"]);

		$skip_status = (x($_REQUEST,'skip_status')?$_REQUEST['skip_status']:false);

		$user_info = api_get_user($a);

		// "verified" isn't used here in the standard
		unset($user_info["verified"]);

		// - Adding last status
		if (!$skip_status) {
			$user_info["status"] = api_status_show($a,"raw");
			if (!count($user_info["status"]))
				unset($user_info["status"]);
			else
				unset($user_info["status"]["user"]);
		}

		// "uid" and "self" are only needed for some internal stuff, so remove it from here
		unset($user_info["uid"]);
		unset($user_info["self"]);

		return api_apply_template("user", $type, array('$user' => $user_info));

	}
	api_register_func('api/account/verify_credentials','api_account_verify_credentials', true);


	/**
	 * get data from $_POST or $_GET
	 */
	function requestdata($k){
		if (isset($_POST[$k])){
			return $_POST[$k];
		}
		if (isset($_GET[$k])){
			return $_GET[$k];
		}
		return null;
	}

/*Waitman Gobble Mod*/
        function api_statuses_mediap(&$a, $type) {
                if (api_user()===false) {
                        logger('api_statuses_update: no user');
                        return false;
                }
                $user_info = api_get_user($a);

                $_REQUEST['type'] = 'wall';
                $_REQUEST['profile_uid'] = api_user();
                $_REQUEST['api_source'] = true;
                $txt = requestdata('status');
                //$txt = urldecode(requestdata('status'));

                require_once('library/HTMLPurifier.auto.php');
                require_once('include/html2bbcode.php');

                if((strpos($txt,'<') !== false) || (strpos($txt,'>') !== false)) {
			$txt = html2bb_video($txt);
			$config = HTMLPurifier_Config::createDefault();
                        $config->set('Cache.DefinitionImpl', null);
			$purifier = new HTMLPurifier($config);
                        $txt = $purifier->purify($txt);
		}
		$txt = html2bbcode($txt);

                $a->argv[1]=$user_info['screen_name']; //should be set to username?

		$_REQUEST['hush']='yeah'; //tell wall_upload function to return img info instead of echo
                require_once('mod/wall_upload.php');
		$bebop = wall_upload_post($a);

		//now that we have the img url in bbcode we can add it to the status and insert the wall item.
                $_REQUEST['body']=$txt."\n\n".$bebop;
                require_once('mod/item.php');
                item_post($a);

                // this should output the last post (the one we just posted).
                return api_status_show($a,$type);
        }
        api_register_func('api/statuses/mediap','api_statuses_mediap', true);
/*Waitman Gobble Mod*/


	function api_statuses_update(&$a, $type) {
		if (api_user()===false) {
			logger('api_statuses_update: no user');
			return false;
		}
		$user_info = api_get_user($a);

		// convert $_POST array items to the form we use for web posts.

		// logger('api_post: ' . print_r($_POST,true));

		if(requestdata('htmlstatus')) {
			require_once('library/HTMLPurifier.auto.php');
			require_once('include/html2bbcode.php');

			$txt = requestdata('htmlstatus');
			if((strpos($txt,'<') !== false) || (strpos($txt,'>') !== false)) {

				$txt = html2bb_video($txt);

				$config = HTMLPurifier_Config::createDefault();
				$config->set('Cache.DefinitionImpl', null);


				$purifier = new HTMLPurifier($config);
				$txt = $purifier->purify($txt);

				$_REQUEST['body'] = html2bbcode($txt);
			}

		}
		else
			$_REQUEST['body'] = requestdata('status');

		$_REQUEST['title'] = requestdata('title');

		$parent = requestdata('in_reply_to_status_id');
		if(ctype_digit($parent))
			$_REQUEST['parent'] = $parent;
		else
			$_REQUEST['parent_uri'] = $parent;

		if(requestdata('lat') && requestdata('long'))
			$_REQUEST['coord'] = sprintf("%s %s",requestdata('lat'),requestdata('long'));
		$_REQUEST['profile_uid'] = api_user();

		if($parent)
			$_REQUEST['type'] = 'net-comment';
		else {
			$_REQUEST['type'] = 'wall';
			if(x($_FILES,'media')) {
				// upload the image if we have one
				$_REQUEST['hush']='yeah'; //tell wall_upload function to return img info instead of echo
				require_once('mod/wall_upload.php');
				$media = wall_upload_post($a);
				if(strlen($media)>0)
					$_REQUEST['body'] .= "\n\n".$media;
			}
		}

		// set this so that the item_post() function is quiet and doesn't redirect or emit json

		$_REQUEST['api_source'] = true;

		// call out normal post function

		require_once('mod/item.php');
		item_post($a);

		// this should output the last post (the one we just posted).
		return api_status_show($a,$type);
	}
	api_register_func('api/statuses/update','api_statuses_update', true);
	api_register_func('api/statuses/update_with_media','api_statuses_update', true);


	function api_status_show(&$a, $type){
		$user_info = api_get_user($a);

		logger('api_status_show: user_info: '.print_r($user_info, true), LOGGER_DEBUG);

		// get last public wall message
		$lastwall = q("SELECT `item`.*, `i`.`contact-id` as `reply_uid`, `c`.`nick` as `reply_author`, `i`.`author-link` AS `item-author`
				FROM `item`, `contact`, `item` as `i`, `contact` as `c`
				WHERE `item`.`contact-id` = %d
					AND ((`item`.`author-link` IN ('%s', '%s')) OR (`item`.`owner-link` IN ('%s', '%s')))
					AND `i`.`id` = `item`.`parent`
					AND `contact`.`id`=`item`.`contact-id` AND `c`.`id`=`i`.`contact-id` AND `contact`.`self`=1
					AND `item`.`type`!='activity'
					AND `item`.`allow_cid`='' AND `item`.`allow_gid`='' AND `item`.`deny_cid`='' AND `item`.`deny_gid`=''
				ORDER BY `item`.`created` DESC
				LIMIT 1",
				intval($user_info['cid']),
				dbesc($user_info['url']),
				dbesc(normalise_link($user_info['url'])),
				dbesc($user_info['url']),
				dbesc(normalise_link($user_info['url']))
		);

		if (count($lastwall)>0){
			$lastwall = $lastwall[0];

			$in_reply_to_status_id = NULL;
			$in_reply_to_user_id = NULL;
			$in_reply_to_status_id_str = NULL;
			$in_reply_to_user_id_str = NULL;
			$in_reply_to_screen_name = NULL;
			if ($lastwall['parent']!=$lastwall['id']) {
				$in_reply_to_status_id= intval($lastwall['parent']);
				$in_reply_to_status_id_str = (string) intval($lastwall['parent']);
				//$in_reply_to_user_id = $lastwall['reply_uid'];
				//$in_reply_to_screen_name = $lastwall['reply_author'];

				$r = q("SELECT * FROM unique_contacts WHERE `url` = '%s'", dbesc(normalise_link($lastwall['item-author'])));
				if ($r) {
					if ($r[0]['nick'] == "")
						$r[0]['nick'] = api_get_nick($r[0]["url"]);

					$in_reply_to_screen_name = (($r[0]['nick']) ? $r[0]['nick'] : $r[0]['name']);
					$in_reply_to_user_id = intval($r[0]['id']);
					$in_reply_to_user_id_str = (string) intval($r[0]['id']);
				}
			}

			$status_info = array(
				'text' => trim(html2plain(bbcode(api_clean_plain_items($lastwall['body']), false, false, 2, true), 0)),
				'truncated' => false,
				'created_at' => api_date($lastwall['created']),
				'in_reply_to_status_id' => $in_reply_to_status_id,
				'in_reply_to_status_id_str' => $in_reply_to_status_id_str,
				'source' => (($lastwall['app']) ? $lastwall['app'] : 'web'),
				'id' => intval($lastwall['id']),
				'id_str' => (string) $lastwall['id'],
				'in_reply_to_user_id' => $in_reply_to_user_id,
				'in_reply_to_user_id_str' => $in_reply_to_user_id_str,
				'in_reply_to_screen_name' => $in_reply_to_screen_name,
				'geo' => NULL,
				'favorited' => false,
				// attachments
				'user' => $user_info,
				'statusnet_html'		=> trim(bbcode($lastwall['body'], false, false)),
				'statusnet_conversation_id'	=> $lastwall['parent'],
			);

			if (($lastwall['item_network'] != "") AND ($status["source"] == 'web'))
				$status_info["source"] = network_to_name($lastwall['item_network']);
			elseif (($lastwall['item_network'] != "") AND (network_to_name($lastwall['item_network']) != $status_info["source"]))
				$status_info["source"] = trim($status_info["source"].' ('.network_to_name($lastwall['item_network']).')');

			// "uid" and "self" are only needed for some internal stuff, so remove it from here
			unset($status_info["user"]["uid"]);
			unset($status_info["user"]["self"]);
		}

		if ($type == "raw")
			return($status_info);

		return  api_apply_template("status", $type, array('$status' => $status_info));

	}





	/**
	 * Returns extended information of a given user, specified by ID or screen name as per the required id parameter.
	 * The author's most recent status will be returned inline.
	 * http://developer.twitter.com/doc/get/users/show
	 */
	function api_users_show(&$a, $type){
		$user_info = api_get_user($a);

		$lastwall = q("SELECT `item`.*
				FROM `item`, `contact`
				WHERE `item`.`contact-id` = %d
					AND ((`item`.`author-link` IN ('%s', '%s')) OR (`item`.`owner-link` IN ('%s', '%s')))
					AND `contact`.`id`=`item`.`contact-id`
					AND `type`!='activity'
					AND `item`.`allow_cid`='' AND `item`.`allow_gid`='' AND `item`.`deny_cid`='' AND `item`.`deny_gid`=''
				ORDER BY `created` DESC
				LIMIT 1",
				intval($user_info['cid']),
				dbesc($user_info['url']),
				dbesc(normalise_link($user_info['url'])),
				dbesc($user_info['url']),
				dbesc(normalise_link($user_info['url']))
		);
//print_r($user_info);
		if (count($lastwall)>0){
			$lastwall = $lastwall[0];

			$in_reply_to_status_id = NULL;
			$in_reply_to_user_id = NULL;
			$in_reply_to_status_id_str = NULL;
			$in_reply_to_user_id_str = NULL;
			$in_reply_to_screen_name = NULL;
			if ($lastwall['parent']!=$lastwall['id']) {
				$reply = q("SELECT `item`.`id`, `item`.`contact-id` as `reply_uid`, `contact`.`nick` as `reply_author`, `item`.`author-link` AS `item-author`
                                            FROM `item`,`contact` WHERE `contact`.`id`=`item`.`contact-id` AND `item`.`id` = %d", intval($lastwall['parent']));
				if (count($reply)>0) {
					$in_reply_to_status_id = intval($lastwall['parent']);
					$in_reply_to_status_id_str = (string) intval($lastwall['parent']);

					$r = q("SELECT * FROM unique_contacts WHERE `url` = '%s'", dbesc(normalise_link($reply[0]['item-author'])));
					if ($r) {
						if ($r[0]['nick'] == "")
							$r[0]['nick'] = api_get_nick($r[0]["url"]);

						$in_reply_to_screen_name = (($r[0]['nick']) ? $r[0]['nick'] : $r[0]['name']);
						$in_reply_to_user_id = intval($r[0]['id']);
						$in_reply_to_user_id_str = (string) intval($r[0]['id']);
					}
				}
			}
			$user_info['status'] = array(
				'text' => trim(html2plain(bbcode(api_clean_plain_items($lastwall['body']), false, false, 2, true), 0)),
				'truncated' => false,
				'created_at' => api_date($lastwall['created']),
				'in_reply_to_status_id' => $in_reply_to_status_id,
				'in_reply_to_status_id_str' => $in_reply_to_status_id_str,
				'source' => (($lastwall['app']) ? $lastwall['app'] : 'web'),
				'id' => intval($lastwall['contact-id']),
				'id_str' => (string) $lastwall['contact-id'],
				'in_reply_to_user_id' => $in_reply_to_user_id,
				'in_reply_to_user_id_str' => $in_reply_to_user_id_str,
				'in_reply_to_screen_name' => $in_reply_to_screen_name,
				'geo' => NULL,
				'favorited' => false,
				'statusnet_html'		=> trim(bbcode($lastwall['body'], false, false)),
				'statusnet_conversation_id'	=> $lastwall['parent'],
			);

			if (($lastwall['item_network'] != "") AND ($user_info["status"]["source"] == 'web'))
				$user_info["status"]["source"] = network_to_name($lastwall['item_network']);
			if (($lastwall['item_network'] != "") AND (network_to_name($lastwall['item_network']) != $user_info["status"]["source"]))
				$user_info["status"]["source"] = trim($user_info["status"]["source"].' ('.network_to_name($lastwall['item_network']).')');

		}

		// "uid" and "self" are only needed for some internal stuff, so remove it from here
		unset($user_info["uid"]);
		unset($user_info["self"]);

		return  api_apply_template("user", $type, array('$user' => $user_info));

	}
	api_register_func('api/users/show','api_users_show');

	/**
	 *
	 * http://developer.twitter.com/doc/get/statuses/home_timeline
	 *
	 * TODO: Optional parameters
	 * TODO: Add reply info
	 */
	function api_statuses_home_timeline(&$a, $type){
		if (api_user()===false) return false;

		unset($_REQUEST["user_id"]);
		unset($_GET["user_id"]);

		unset($_REQUEST["screen_name"]);
		unset($_GET["screen_name"]);

		$user_info = api_get_user($a);
		// get last newtork messages


		// params
		$count = (x($_REQUEST,'count')?$_REQUEST['count']:20);
		$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
		if ($page<0) $page=0;
		$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		$max_id = (x($_REQUEST,'max_id')?$_REQUEST['max_id']:0);
		//$since_id = 0;//$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		$exclude_replies = (x($_REQUEST,'exclude_replies')?1:0);
		$conversation_id = (x($_REQUEST,'conversation_id')?$_REQUEST['conversation_id']:0);

		$start = $page*$count;

		//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);

		$sql_extra = '';
		if ($max_id > 0)
			$sql_extra .= ' AND `item`.`id` <= '.intval($max_id);
		if ($exclude_replies > 0)
			$sql_extra .= ' AND `item`.`parent` = `item`.`id`';
		if ($conversation_id > 0)
			$sql_extra .= ' AND `item`.`parent` = '.intval($conversation_id);

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, `item`.`network` AS `item_network`,
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d
			AND `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			AND `item`.`id`>%d
			ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
			//intval($user_info['uid']),
			intval(api_user()),
			intval($since_id),
			intval($start),	intval($count)
		);

		$ret = api_format_items($r,$user_info);

		// We aren't going to try to figure out at the item, group, and page
		// level which items you've seen and which you haven't. If you're looking
		// at the network timeline just mark everything seen. 

		$r = q("UPDATE `item` SET `unseen` = 0 
			WHERE `unseen` = 1 AND `uid` = %d",
			//intval($user_info['uid'])
			intval(api_user())
		);


		$data = array('$statuses' => $ret);
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
				break;
			case "as":
				$as = api_format_as($a, $ret, $user_info);
				$as['title'] = $a->config['sitename']." Home Timeline";
				$as['link']['url'] = $a->get_baseurl()."/".$user_info["screen_name"]."/all";
				return($as);
				break;
		}

		return  api_apply_template("timeline", $type, $data);
	}
	api_register_func('api/statuses/home_timeline','api_statuses_home_timeline', true);
	api_register_func('api/statuses/friends_timeline','api_statuses_home_timeline', true);

	function api_statuses_public_timeline(&$a, $type){
		if (api_user()===false) return false;

		$user_info = api_get_user($a);
		// get last newtork messages


		// params
		$count = (x($_REQUEST,'count')?$_REQUEST['count']:20);
		$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
		if ($page<0) $page=0;
		$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		$max_id = (x($_REQUEST,'max_id')?$_REQUEST['max_id']:0);
		//$since_id = 0;//$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		$exclude_replies = (x($_REQUEST,'exclude_replies')?1:0);
		$conversation_id = (x($_REQUEST,'conversation_id')?$_REQUEST['conversation_id']:0);

		$start = $page*$count;

		//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);

		if ($max_id > 0)
			$sql_extra = 'AND `item`.`id` <= '.intval($max_id);
		if ($exclude_replies > 0)
			$sql_extra .= ' AND `item`.`parent` = `item`.`id`';
		if ($conversation_id > 0)
			$sql_extra .= ' AND `item`.`parent` = '.intval($conversation_id);

	        $r = q("SELECT `item`.*, `item`.`id` AS `item_id`, `item`.`network` AS `item_network`,
	                `contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
        	        `contact`.`network`, `contact`.`thumb`, `contact`.`self`, `contact`.`writable`,
                	`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`,
                	`user`.`nickname`, `user`.`hidewall`
                	FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
                	LEFT JOIN `user` ON `user`.`uid` = `item`.`uid`
                	WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 and `item`.`moderated` = 0
                	AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = ''
                	AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = ''
                	AND `item`.`private` = 0 AND `item`.`wall` = 1 AND `user`.`hidewall` = 0
                	AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			AND `item`.`id`>%d
                	ORDER BY `received` DESC LIMIT %d, %d ",
			intval($since_id),
                	intval($start),
                	intval($count));

		$ret = api_format_items($r,$user_info);


		$data = array('$statuses' => $ret);
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
				break;
			case "as":
				$as = api_format_as($a, $ret, $user_info);
				$as['title'] = $a->config['sitename']." Public Timeline";
				$as['link']['url'] = $a->get_baseurl()."/";
				return($as);
				break;
		}

		return  api_apply_template("timeline", $type, $data);
	}
	api_register_func('api/statuses/public_timeline','api_statuses_public_timeline', true);

	/**
	 * 
	 */
	function api_statuses_show(&$a, $type){
		if (api_user()===false) return false;

		$user_info = api_get_user($a);

		// params
		$id = intval($a->argv[3]);

		if ($id == 0)
			$id = intval($_REQUEST["id"]);

		// Hotot workaround
		if ($id == 0)
			$id = intval($a->argv[4]);

		logger('API: api_statuses_show: '.$id);

		//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);
		$conversation = (x($_REQUEST,'conversation')?1:0);

		$sql_extra = '';
		if ($conversation)
			$sql_extra .= " AND `item`.`parent` = %d ORDER BY `received` ASC ";
		else
			$sql_extra .= " AND `item`.`id` = %d";

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, `item`.`network` AS `item_network`,
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra",
			intval($id)
		);

		if (!$r)
			die(api_error($a, $type, t("There is no status with this id.")));

		$ret = api_format_items($r,$user_info);

		if ($conversation) {
			$data = array('$statuses' => $ret);
			return api_apply_template("timeline", $type, $data);
		} else {
			$data = array('$status' => $ret[0]);
			/*switch($type){
				case "atom":
				case "rss":
					$data = api_rss_extra($a, $data, $user_info);
			}*/
			return  api_apply_template("status", $type, $data);
		}
	}
	api_register_func('api/statuses/show','api_statuses_show', true);


	/**
	 *
	 */
	function api_conversation_show(&$a, $type){
		if (api_user()===false) return false;

		$user_info = api_get_user($a);

		// params
		$id = intval($a->argv[3]);
		$count = (x($_REQUEST,'count')?$_REQUEST['count']:20);
		$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
		if ($page<0) $page=0;
		$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		$max_id = (x($_REQUEST,'max_id')?$_REQUEST['max_id']:0);

		$start = $page*$count;

		if ($id == 0)
			$id = intval($_REQUEST["id"]);

		// Hotot workaround
		if ($id == 0)
			$id = intval($a->argv[4]);

		logger('API: api_conversation_show: '.$id);

		//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);

		$sql_extra = '';

		if ($max_id > 0)
			$sql_extra = ' AND `item`.`id` <= '.intval($max_id);

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, `item`.`network` AS `item_network`,
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item` INNER JOIN (SELECT `uri`,`parent` FROM `item` WHERE `id` = %d) AS `temp1`
			ON (`item`.`thr-parent` = `temp1`.`uri` AND `item`.`parent` = `temp1`.`parent`), `contact`
			WHERE `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`deleted` = 0
			AND `item`.`uid` = %d AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			AND `item`.`id`>%d $sql_extra
			ORDER BY `item`.`received` DESC LIMIT %d ,%d",
			intval($id), intval(api_user()),
                        intval($since_id),
                        intval($start), intval($count)
		);

		if (!$r)
			die(api_error($a, $type, t("There is no conversation with this id.")));

		$ret = api_format_items($r,$user_info);

		$data = array('$statuses' => $ret);
		return api_apply_template("timeline", $type, $data);
	}
	api_register_func('api/conversation/show','api_conversation_show', true);


	/**
	 *
	 */
	function api_statuses_repeat(&$a, $type){
		global $called_api;

		if (api_user()===false) return false;

		$user_info = api_get_user($a);

		// params
		$id = intval($a->argv[3]);

		if ($id == 0)
			$id = intval($_REQUEST["id"]);

		// Hotot workaround
		if ($id == 0)
			$id = intval($a->argv[4]);

		logger('API: api_statuses_repeat: '.$id);

		//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, `item`.`network` AS `item_network`, `contact`.`nick` as `reply_author`,
			`contact`.`name`, `contact`.`photo` as `reply_photo`, `contact`.`url` as `reply_url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			AND `item`.`id`=%d",
			intval($id)
		);

		if ($r[0]['body'] != "") {
			if (!intval(get_config('system','old_share'))) {
				if (strpos($r[0]['body'], "[/share]") !== false) {
					$pos = strpos($r[0]['body'], "[share");
					$post = substr($r[0]['body'], $pos);
				} else {
					$post = "[share author='".str_replace("'", "&#039;", $r[0]['author-name']).
							"' profile='".$r[0]['author-link'].
							"' avatar='".$r[0]['author-avatar'].
							"' link='".$r[0]['plink']."']";
					$post .= $r[0]['body'];
					$post .= "[/share]";
				}
				$_REQUEST['body'] = $post;
			} else
				$_REQUEST['body'] = html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8')."[url=".$r[0]['reply_url']."]".$r[0]['reply_author']."[/url] \n".$r[0]['body'];

			$_REQUEST['profile_uid'] = api_user();
			$_REQUEST['type'] = 'wall';
			$_REQUEST['api_source'] = true;

			require_once('mod/item.php');
			item_post($a);
		}

		// this should output the last post (the one we just posted).
		$called_api = null;
		return(api_status_show($a,$type));
	}
	api_register_func('api/statuses/retweet','api_statuses_repeat', true);

	/**
	 *
	 */
	function api_statuses_destroy(&$a, $type){
		if (api_user()===false) return false;

		$user_info = api_get_user($a);

		// params
		$id = intval($a->argv[3]);

		if ($id == 0)
			$id = intval($_REQUEST["id"]);

		// Hotot workaround
		if ($id == 0)
			$id = intval($a->argv[4]);

		logger('API: api_statuses_destroy: '.$id);

		$ret = api_statuses_show($a, $type);

		require_once('include/items.php');
		drop_item($id, false);

		return($ret);
	}
	api_register_func('api/statuses/destroy','api_statuses_destroy', true);

	/**
	 * 
	 * http://developer.twitter.com/doc/get/statuses/mentions
	 * 
	 */
	function api_statuses_mentions(&$a, $type){
		if (api_user()===false) return false;

		unset($_REQUEST["user_id"]);
		unset($_GET["user_id"]);

		unset($_REQUEST["screen_name"]);
		unset($_GET["screen_name"]);

		$user_info = api_get_user($a);
		// get last newtork messages


		// params
		$count = (x($_REQUEST,'count')?$_REQUEST['count']:20);
		$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
		if ($page<0) $page=0;
		$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		$max_id = (x($_REQUEST,'max_id')?$_REQUEST['max_id']:0);
		//$since_id = 0;//$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);

		$start = $page*$count;

		//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);

		// Ugly code - should be changed
		$myurl = $a->get_baseurl() . '/profile/'. $a->user['nickname'];
		$myurl = substr($myurl,strpos($myurl,'://')+3);
		//$myurl = str_replace(array('www.','.'),array('','\\.'),$myurl);
		$myurl = str_replace('www.','',$myurl);
		$diasp_url = str_replace('/profile/','/u/',$myurl);

		$sql_extra .= sprintf(" AND `item`.`parent` IN (SELECT distinct(`parent`) from item where `author-link` IN ('https://%s', 'http://%s') OR `mention`)",
			dbesc(protect_sprintf($myurl)),
			dbesc(protect_sprintf($myurl))
		);

		if ($max_id > 0)
			$sql_extra .= ' AND `item`.`id` <= '.intval($max_id);

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, `item`.`network` AS `item_network`,
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d
			AND `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			AND `item`.`id`>%d
			ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
			//intval($user_info['uid']),
			intval(api_user()),
			intval($since_id),
			intval($start),	intval($count)
		);

		$ret = api_format_items($r,$user_info);


		$data = array('$statuses' => $ret);
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
				break;
			case "as":
				$as = api_format_as($a, $ret, $user_info);
				$as["title"] = $a->config['sitename']." Mentions";
				$as['link']['url'] = $a->get_baseurl()."/";
				return($as);
				break;
		}

		return  api_apply_template("timeline", $type, $data);
	}
	api_register_func('api/statuses/mentions','api_statuses_mentions', true);
	api_register_func('api/statuses/replies','api_statuses_mentions', true);


	function api_statuses_user_timeline(&$a, $type){
		if (api_user()===false) return false;

		$user_info = api_get_user($a);
		// get last network messages

		logger("api_statuses_user_timeline: api_user: ". api_user() .
			   "\nuser_info: ".print_r($user_info, true) .
			   "\n_REQUEST:  ".print_r($_REQUEST, true),
			   LOGGER_DEBUG);

		// params
		$count = (x($_REQUEST,'count')?$_REQUEST['count']:20);
		$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
		if ($page<0) $page=0;
		$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		//$since_id = 0;//$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		$exclude_replies = (x($_REQUEST,'exclude_replies')?1:0);
		$conversation_id = (x($_REQUEST,'conversation_id')?$_REQUEST['conversation_id']:0);

		$start = $page*$count;

		$sql_extra = '';
		if ($user_info['self']==1)
			$sql_extra .= " AND `item`.`wall` = 1 ";

		if ($exclude_replies > 0)
			$sql_extra .= ' AND `item`.`parent` = `item`.`id`';
		if ($conversation_id > 0)
			$sql_extra .= ' AND `item`.`parent` = '.intval($conversation_id);

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, `item`.`network` AS `item_network`,
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d
			AND `item`.`contact-id` = %d
			AND `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			AND `item`.`id`>%d
			ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
			intval(api_user()),
			intval($user_info['cid']),
			intval($since_id),
			intval($start),	intval($count)
		);

		$ret = api_format_items($r,$user_info, true);

		$data = array('$statuses' => $ret);
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
		}

		return  api_apply_template("timeline", $type, $data);
	}

	api_register_func('api/statuses/user_timeline','api_statuses_user_timeline', true);


	function api_favorites(&$a, $type){
		global $called_api;

		if (api_user()===false) return false;

		$called_api= array();

		$user_info = api_get_user($a);

		// in friendica starred item are private
		// return favorites only for self
		logger('api_favorites: self:' . $user_info['self']);

		if ($user_info['self']==0) {
			$ret = array();
		} else {
			$sql_extra = "";

			// params
			$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
			$max_id = (x($_REQUEST,'max_id')?$_REQUEST['max_id']:0);
			$count = (x($_GET,'count')?$_GET['count']:20);
			$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
			if ($page<0) $page=0;

			$start = $page*$count;

			if ($max_id > 0)
				$sql_extra .= ' AND `item`.`id` <= '.intval($max_id);

			$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, `item`.`network` AS `item_network`,
				`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
				`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
				`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
				FROM `item`, `contact`
				WHERE `item`.`uid` = %d
				AND `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`deleted` = 0
				AND `item`.`starred` = 1
				AND `contact`.`id` = `item`.`contact-id`
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				$sql_extra
				AND `item`.`id`>%d
				ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
				//intval($user_info['uid']),
				intval(api_user()),
				intval($since_id),
				intval($start),	intval($count)
			);

			$ret = api_format_items($r,$user_info);

		}

		$data = array('$statuses' => $ret);
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
		}

		return  api_apply_template("timeline", $type, $data);
	}

	api_register_func('api/favorites','api_favorites', true);

	function api_format_as($a, $ret, $user_info) {

		$as = array();
		$as['title'] = $a->config['sitename']." Public Timeline";
		$items = array();
		foreach ($ret as $item) {
			$singleitem["actor"]["displayName"] = $item["user"]["name"];
			$singleitem["actor"]["id"] = $item["user"]["contact_url"];
			$avatar[0]["url"] = $item["user"]["profile_image_url"];
			$avatar[0]["rel"] = "avatar";
			$avatar[0]["type"] = "";
			$avatar[0]["width"] = 96;
			$avatar[0]["height"] = 96;
			$avatar[1]["url"] = $item["user"]["profile_image_url"];
			$avatar[1]["rel"] = "avatar";
			$avatar[1]["type"] = "";
			$avatar[1]["width"] = 48;
			$avatar[1]["height"] = 48;
			$avatar[2]["url"] = $item["user"]["profile_image_url"];
			$avatar[2]["rel"] = "avatar";
			$avatar[2]["type"] = "";
			$avatar[2]["width"] = 24;
			$avatar[2]["height"] = 24;
			$singleitem["actor"]["avatarLinks"] = $avatar;

			$singleitem["actor"]["image"]["url"] = $item["user"]["profile_image_url"];
			$singleitem["actor"]["image"]["rel"] = "avatar";
			$singleitem["actor"]["image"]["type"] = "";
			$singleitem["actor"]["image"]["width"] = 96;
			$singleitem["actor"]["image"]["height"] = 96;
			$singleitem["actor"]["type"] = "person";
			$singleitem["actor"]["url"] = $item["person"]["contact_url"];
			$singleitem["actor"]["statusnet:profile_info"]["local_id"] = $item["user"]["id"];
			$singleitem["actor"]["statusnet:profile_info"]["following"] = $item["user"]["following"] ? "true" : "false";
			$singleitem["actor"]["statusnet:profile_info"]["blocking"] = "false";
			$singleitem["actor"]["contact"]["preferredUsername"] = $item["user"]["screen_name"];
			$singleitem["actor"]["contact"]["displayName"] = $item["user"]["name"];
			$singleitem["actor"]["contact"]["addresses"] = "";

			$singleitem["body"] = $item["text"];
			$singleitem["object"]["displayName"] = $item["text"];
			$singleitem["object"]["id"] = $item["url"];
			$singleitem["object"]["type"] = "note";
			$singleitem["object"]["url"] = $item["url"];
			//$singleitem["context"] =;
			$singleitem["postedTime"] = date("c", strtotime($item["published"]));
			$singleitem["provider"]["objectType"] = "service";
			$singleitem["provider"]["displayName"] = "Test";
			$singleitem["provider"]["url"] = "http://test.tld";
			$singleitem["title"] = $item["text"];
			$singleitem["verb"] = "post";
			$singleitem["statusnet:notice_info"]["local_id"] = $item["id"];
				$singleitem["statusnet:notice_info"]["source"] = $item["source"];
				$singleitem["statusnet:notice_info"]["favorite"] = "false";
				$singleitem["statusnet:notice_info"]["repeated"] = "false";
				//$singleitem["original"] = $item;
				$items[] = $singleitem;
		}
		$as['items'] = $items;
		$as['link']['url'] = $a->get_baseurl()."/".$user_info["screen_name"]."/all";
		$as['link']['rel'] = "alternate";
		$as['link']['type'] = "text/html";
		return($as);
	}

	function api_format_messages($item, $recipient, $sender) {
		// standard meta information
		$ret=Array(
				'id'                    => $item['id'],
				'sender_id'             => $sender['id'] ,
				'text'                  => "",
				'recipient_id'          => $recipient['id'],
				'created_at'            => api_date($item['created']),
				'sender_screen_name'    => $sender['screen_name'],
				'recipient_screen_name' => $recipient['screen_name'],
				'sender'                => $sender,
				'recipient'             => $recipient,
		);

		// "uid" and "self" are only needed for some internal stuff, so remove it from here
		unset($ret["sender"]["uid"]);
		unset($ret["sender"]["self"]);
		unset($ret["recipient"]["uid"]);
		unset($ret["recipient"]["self"]);

		//don't send title to regular StatusNET requests to avoid confusing these apps
		if (x($_GET, 'getText')) {
			$ret['title'] = $item['title'] ;
			if ($_GET["getText"] == "html") {
				$ret['text'] = bbcode($item['body'], false, false);
			}
			elseif ($_GET["getText"] == "plain") {
				//$ret['text'] = html2plain(bbcode($item['body'], false, false, true), 0);
				$ret['text'] = trim(html2plain(bbcode(api_clean_plain_items($item['body']), false, false, 2, true), 0));
			}
		}
		else {
			$ret['text'] = $item['title']."\n".html2plain(bbcode(api_clean_plain_items($item['body']), false, false, 2, true), 0);
		}
		if (isset($_GET["getUserObjects"]) && $_GET["getUserObjects"] == "false") {
			unset($ret['sender']);
			unset($ret['recipient']);
		}

		return $ret;
	}

	function api_format_items($r,$user_info, $filter_user = false) {

		$a = get_app();
		$ret = Array();

		foreach($r as $item) {
			api_share_as_retweet($a, api_user(), $item);

			localize_item($item);
			$status_user = api_item_get_user($a,$item);

			// Look if the posts are matching if they should be filtered by user id
			if ($filter_user AND ($status_user["id"] != $user_info["id"]))
				continue;

			if ($item['thr-parent'] != $item['uri']) {
				$r = q("SELECT id FROM item WHERE uid=%d AND uri='%s' LIMIT 1",
					intval(api_user()),
					dbesc($item['thr-parent']));
				if ($r)
					$in_reply_to_status_id = intval($r[0]['id']);
				else
					$in_reply_to_status_id = intval($item['parent']);

				$in_reply_to_status_id_str = (string) intval($item['parent']);

				$in_reply_to_screen_name = NULL;
				$in_reply_to_user_id = NULL;
				$in_reply_to_user_id_str = NULL;

				$r = q("SELECT `author-link` FROM item WHERE uid=%d AND id=%d LIMIT 1",
					intval(api_user()),
					intval($in_reply_to_status_id));
				if ($r) {
					$r = q("SELECT * FROM unique_contacts WHERE `url` = '%s'", dbesc(normalise_link($r[0]['author-link'])));

					if ($r) {
						if ($r[0]['nick'] == "")
							$r[0]['nick'] = api_get_nick($r[0]["url"]);

						$in_reply_to_screen_name = (($r[0]['nick']) ? $r[0]['nick'] : $r[0]['name']);
						$in_reply_to_user_id = intval($r[0]['id']);
						$in_reply_to_user_id_str = (string) intval($r[0]['id']);
					}
				}
			} else {
				$in_reply_to_screen_name = NULL;
				$in_reply_to_user_id = NULL;
				$in_reply_to_status_id = NULL;
				$in_reply_to_user_id_str = NULL;
				$in_reply_to_status_id_str = NULL;
			}

			// Workaround for ostatus messages where the title is identically to the body
			$statusbody = trim(html2plain(bbcode(api_clean_plain_items($item['body']), false, false, 2, true), 0));

			$statustitle = trim($item['title']);

			if (($statustitle != '') and (strpos($statusbody, $statustitle) !== false))
				$statustext = trim($statusbody);
			else
				$statustext = trim($statustitle."\n\n".$statusbody);

			if (($item["network"] == NETWORK_FEED) and (strlen($statustext)> 1000))
				$statustext = substr($statustext, 0, 1000)."... \n".$item["plink"];

			$status = array(
				'text'		=> $statustext,
				'truncated' => False,
				'created_at'=> api_date($item['created']),
				'in_reply_to_status_id' => $in_reply_to_status_id,
				'in_reply_to_status_id_str' => $in_reply_to_status_id,
				'source'    => (($item['app']) ? $item['app'] : 'web'),
				'id'		=> intval($item['id']),
				'id_str'	=> (string) intval($item['id']),
				'in_reply_to_user_id' => $in_reply_to_user_id,
				'in_reply_to_user_id_str' => $in_reply_to_user_id,
				'in_reply_to_screen_name' => $in_reply_to_screen_name,
				'geo' => NULL,
				'favorited' => $item['starred'] ? true : false,
				//'attachments' => array(),
				'user' =>  $status_user ,
				'statusnet_html'		=> trim(bbcode($item['body'], false, false)),
				'statusnet_conversation_id'	=> $item['parent'],
			);

			if (($item['item_network'] != "") AND ($status["source"] == 'web'))
				$status["source"] = network_to_name($item['item_network']);
			else if (($item['item_network'] != "") AND (network_to_name($item['item_network']) != $status["source"]))
				$status["source"] = trim($status["source"].' ('.network_to_name($item['item_network']).')');


			// Retweets are only valid for top postings
			if (($item['owner-link'] != $item['author-link']) AND ($item["id"] == $item["parent"])) {
				$retweeted_status = $status;
				$retweeted_status["user"] = api_get_user($a,$item["author-link"]);

				$status["retweeted_status"] = $retweeted_status;
			}

			// "uid" and "self" are only needed for some internal stuff, so remove it from here
			unset($status["user"]["uid"]);
			unset($status["user"]["self"]);

			// 'geo' => array('type' => 'Point',
                        //                   'coordinates' => array((float) $notice->lat,
                        //                                          (float) $notice->lon));

			// Seesmic doesn't like the following content
			// completely disabled to make friendica totally compatible to the statusnet API
			/*if ($_SERVER['HTTP_USER_AGENT'] != 'Seesmic') {
				$status2 = array(
					'updated'   => api_date($item['edited']),
					'published' => api_date($item['created']),
					'message_id' => $item['uri'],
					'url'		=> ($item['plink']!=''?$item['plink']:$item['author-link']),
					'coordinates' => $item['coord'],
					'place' => $item['location'],
					'contributors' => '',
					'annotations'  => '',
					'entities'  => '',
					'objecttype' => (($item['object-type']) ? $item['object-type'] : ACTIVITY_OBJ_NOTE),
					'verb' => (($item['verb']) ? $item['verb'] : ACTIVITY_POST),
					'self' => $a->get_baseurl()."/api/statuses/show/".$item['id'].".".$type,
					'edit' => $a->get_baseurl()."/api/statuses/show/".$item['id'].".".$type,
				);

				$status = array_merge($status, $status2);
			}*/

			$ret[] = $status;
		};
		return $ret;
	}


	function api_account_rate_limit_status(&$a,$type) {

		$hash = array(
			  'reset_time_in_seconds' => strtotime('now + 1 hour'),
			  'remaining_hits' => (string) 150,
			  'hourly_limit' => (string) 150,
			  'reset_time' => api_date(datetime_convert('UTC','UTC','now + 1 hour',ATOM_TIME)),
		);
		if ($type == "xml")
			$hash['resettime_in_seconds'] = $hash['reset_time_in_seconds'];

		return api_apply_template('ratelimit', $type, array('$hash' => $hash));

	}
	api_register_func('api/account/rate_limit_status','api_account_rate_limit_status',true);

	function api_help_test(&$a,$type) {

		if ($type == 'xml')
			$ok = "true";
		else
			$ok = "ok";

		return api_apply_template('test', $type, array("$ok" => $ok));

	}
	api_register_func('api/help/test','api_help_test',false);

	/**
	 *  https://dev.twitter.com/docs/api/1/get/statuses/friends
	 *  This function is deprecated by Twitter
	 *  returns: json, xml
	 **/
	function api_statuses_f(&$a, $type, $qtype) {
		if (api_user()===false) return false;
		$user_info = api_get_user($a);

		if (x($_GET,'cursor') && $_GET['cursor']=='undefined'){
			/* this is to stop Hotot to load friends multiple times
			*  I'm not sure if I'm missing return something or
			*  is a bug in hotot. Workaround, meantime
			*/

			/*$ret=Array();
			return array('$users' => $ret);*/
			return false;
		}

		if($qtype == 'friends')
			$sql_extra = sprintf(" AND ( `rel` = %d OR `rel` = %d ) ", intval(CONTACT_IS_SHARING), intval(CONTACT_IS_FRIEND));
		if($qtype == 'followers')
			$sql_extra = sprintf(" AND ( `rel` = %d OR `rel` = %d ) ", intval(CONTACT_IS_FOLLOWER), intval(CONTACT_IS_FRIEND));

		// friends and followers only for self
		if ($user_info['self'] == 0)
			$sql_extra = " AND false ";

		$r = q("SELECT `nurl` FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 AND `pending` = 0 $sql_extra",
			intval(api_user())
		);

		$ret = array();
		foreach($r as $cid){
			$user = api_get_user($a, $cid['nurl']);
			// "uid" and "self" are only needed for some internal stuff, so remove it from here
			unset($user["uid"]);
			unset($user["self"]);

			if ($user)
				$ret[] = $user;
		}

		return array('$users' => $ret);

	}
	function api_statuses_friends(&$a, $type){
		$data =  api_statuses_f($a,$type,"friends");
		if ($data===false) return false;
		return  api_apply_template("friends", $type, $data);
	}
	function api_statuses_followers(&$a, $type){
		$data = api_statuses_f($a,$type,"followers");
		if ($data===false) return false;
		return  api_apply_template("friends", $type, $data);
	}
	api_register_func('api/statuses/friends','api_statuses_friends',true);
	api_register_func('api/statuses/followers','api_statuses_followers',true);






	function api_statusnet_config(&$a,$type) {
		$name = $a->config['sitename'];
		$server = $a->get_hostname();
		$logo = $a->get_baseurl() . '/images/friendica-64.png';
		$email = $a->config['admin_email'];
		$closed = (($a->config['register_policy'] == REGISTER_CLOSED) ? 'true' : 'false');
		$private = (($a->config['system']['block_public']) ? 'true' : 'false');
		$textlimit = (string) (($a->config['max_import_size']) ? $a->config['max_import_size'] : 200000);
		if($a->config['api_import_size'])
			$texlimit = string($a->config['api_import_size']);
		$ssl = (($a->config['system']['have_ssl']) ? 'true' : 'false');
		$sslserver = (($ssl === 'true') ? str_replace('http:','https:',$a->get_baseurl()) : '');

		$config = array(
			'site' => array('name' => $name,'server' => $server, 'theme' => 'default', 'path' => '',
				'logo' => $logo, 'fancy' => true, 'language' => 'en', 'email' => $email, 'broughtby' => '',
				'broughtbyurl' => '', 'timezone' => 'UTC', 'closed' => $closed, 'inviteonly' => false,
				'private' => $private, 'textlimit' => $textlimit, 'sslserver' => $sslserver, 'ssl' => $ssl,
				'shorturllength' => '30',
				'friendica' => array(
						'FRIENDICA_PLATFORM' => FRIENDICA_PLATFORM,
						'FRIENDICA_VERSION' => FRIENDICA_VERSION,
						'DFRN_PROTOCOL_VERSION' => DFRN_PROTOCOL_VERSION,
						'DB_UPDATE_VERSION' => DB_UPDATE_VERSION
						)
			),
		);

		return api_apply_template('config', $type, array('$config' => $config));

	}
	api_register_func('api/statusnet/config','api_statusnet_config',false);

	function api_statusnet_version(&$a,$type) {

		// liar

		if($type === 'xml') {
			header("Content-type: application/xml");
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" . '<version>0.9.7</version>' . "\r\n";
			killme();
		}
		elseif($type === 'json') {
			header("Content-type: application/json");
			echo '"0.9.7"';
			killme();
		}
	}
	api_register_func('api/statusnet/version','api_statusnet_version',false);


	function api_ff_ids(&$a,$type,$qtype) {
		if(! api_user())
			return false;

		$user_info = api_get_user($a);

		if($qtype == 'friends')
			$sql_extra = sprintf(" AND ( `rel` = %d OR `rel` = %d ) ", intval(CONTACT_IS_SHARING), intval(CONTACT_IS_FRIEND));
		if($qtype == 'followers')
			$sql_extra = sprintf(" AND ( `rel` = %d OR `rel` = %d ) ", intval(CONTACT_IS_FOLLOWER), intval(CONTACT_IS_FRIEND));

		if (!$user_info["self"])
			$sql_extra = " AND false ";

		$stringify_ids = (x($_REQUEST,'stringify_ids')?$_REQUEST['stringify_ids']:false);

		$r = q("SELECT unique_contacts.id FROM contact, unique_contacts WHERE contact.nurl = unique_contacts.url AND `uid` = %d AND `self` = 0 AND `blocked` = 0 AND `pending` = 0 $sql_extra",
			intval(api_user())
		);

		if(is_array($r)) {

			if($type === 'xml') {
				header("Content-type: application/xml");
				echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" . '<ids>' . "\r\n";
				foreach($r as $rr)
					echo '<id>' . $rr['id'] . '</id>' . "\r\n";
				echo '</ids>' . "\r\n";
				killme();
			}
			elseif($type === 'json') {
				$ret = array();
				header("Content-type: application/json");
				foreach($r as $rr)
					if ($stringify_ids)
						$ret[] = $rr['id'];
					else
						$ret[] = intval($rr['id']);

				echo json_encode($ret);
				killme();
			}
		}
	}

	function api_friends_ids(&$a,$type) {
		api_ff_ids($a,$type,'friends');
	}
	function api_followers_ids(&$a,$type) {
		api_ff_ids($a,$type,'followers');
	}
	api_register_func('api/friends/ids','api_friends_ids',true);
	api_register_func('api/followers/ids','api_followers_ids',true);


	function api_direct_messages_new(&$a, $type) {
		if (api_user()===false) return false;

		if (!x($_POST, "text") OR (!x($_POST,"screen_name") AND !x($_POST,"user_id"))) return;

		$sender = api_get_user($a);

		require_once("include/message.php");

		if ($_POST['screen_name']) {
			$r = q("SELECT `id`, `nurl`, `network` FROM `contact` WHERE `uid`=%d AND `nick`='%s'",
					intval(api_user()),
					dbesc($_POST['screen_name']));

			// Selecting the id by priority, friendica first
			api_best_nickname($r);

			$recipient = api_get_user($a, $r[0]['nurl']);
		} else
			$recipient = api_get_user($a, $_POST['user_id']);

		$replyto = '';
		$sub     = '';
		if (x($_REQUEST,'replyto')) {
			$r = q('SELECT `parent-uri`, `title` FROM `mail` WHERE `uid`=%d AND `id`=%d',
					intval(api_user()),
					intval($_REQUEST['replyto']));
			$replyto = $r[0]['parent-uri'];
			$sub     = $r[0]['title'];
		}
		else {
			if (x($_REQUEST,'title')) {
				$sub = $_REQUEST['title'];
			}
			else {
				$sub = ((strlen($_POST['text'])>10)?substr($_POST['text'],0,10)."...":$_POST['text']);
			}
		}

		$id = send_message($recipient['cid'], $_POST['text'], $sub, $replyto);

		if ($id>-1) {
			$r = q("SELECT * FROM `mail` WHERE id=%d", intval($id));
			$ret = api_format_messages($r[0], $recipient, $sender);

		} else {
			$ret = array("error"=>$id);
		}

		$data = Array('$messages'=>$ret);

		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
		}

		return  api_apply_template("direct_messages", $type, $data);

	}
	api_register_func('api/direct_messages/new','api_direct_messages_new',true);

	function api_direct_messages_box(&$a, $type, $box) {
		if (api_user()===false) return false;

		unset($_REQUEST["user_id"]);
		unset($_GET["user_id"]);

		unset($_REQUEST["screen_name"]);
		unset($_GET["screen_name"]);

		$user_info = api_get_user($a);

		// params
		$count = (x($_GET,'count')?$_GET['count']:20);
		$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
		if ($page<0) $page=0;

		$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		$max_id = (x($_REQUEST,'max_id')?$_REQUEST['max_id']:0);

		$start = $page*$count;

		//$profile_url = $a->get_baseurl() . '/profile/' . $a->user['nickname'];
		$profile_url = $user_info["url"];

		if ($box=="sentbox") {
			$sql_extra = "`mail`.`from-url`='".dbesc( $profile_url )."'";
		}
		elseif ($box=="conversation") {
			$sql_extra = "`mail`.`parent-uri`='".dbesc( $_GET["uri"] )  ."'";
		}
		elseif ($box=="all") {
			$sql_extra = "true";
		}
		elseif ($box=="inbox") {
			$sql_extra = "`mail`.`from-url`!='".dbesc( $profile_url )."'";
		}

		if ($max_id > 0)
			$sql_extra .= ' AND `mail`.`id` <= '.intval($max_id);

		$r = q("SELECT `mail`.*, `contact`.`nurl` AS `contact-url` FROM `mail`,`contact` WHERE `mail`.`contact-id` = `contact`.`id` AND `mail`.`uid`=%d AND $sql_extra AND `mail`.`id` > %d ORDER BY `mail`.`created` DESC LIMIT %d,%d",
				intval(api_user()),
				intval($since_id),
				intval($start),	intval($count)
		);

		$ret = Array();
		foreach($r as $item) {
			if ($box == "inbox" || $item['from-url'] != $profile_url){
				$recipient = $user_info;
				$sender = api_get_user($a,normalise_link($item['contact-url']));
			}
			elseif ($box == "sentbox" || $item['from-url'] != $profile_url){
				$recipient = api_get_user($a,normalise_link($item['contact-url']));
				$sender = $user_info;

			}

			$ret[]=api_format_messages($item, $recipient, $sender);
		}


		$data = array('$messages' => $ret);
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
		}

		return  api_apply_template("direct_messages", $type, $data);

	}

	function api_direct_messages_sentbox(&$a, $type){
		return api_direct_messages_box($a, $type, "sentbox");
	}
	function api_direct_messages_inbox(&$a, $type){
		return api_direct_messages_box($a, $type, "inbox");
	}
	function api_direct_messages_all(&$a, $type){
		return api_direct_messages_box($a, $type, "all");
	}
	function api_direct_messages_conversation(&$a, $type){
		return api_direct_messages_box($a, $type, "conversation");
	}
	api_register_func('api/direct_messages/conversation','api_direct_messages_conversation',true);
	api_register_func('api/direct_messages/all','api_direct_messages_all',true);
	api_register_func('api/direct_messages/sent','api_direct_messages_sentbox',true);
	api_register_func('api/direct_messages','api_direct_messages_inbox',true);



	function api_oauth_request_token(&$a, $type){
		try{
			$oauth = new FKOAuth1();
			$r = $oauth->fetch_request_token(OAuthRequest::from_request());
		}catch(Exception $e){
			echo "error=". OAuthUtil::urlencode_rfc3986($e->getMessage()); killme();
		}
		echo $r;
		killme();
	}
	function api_oauth_access_token(&$a, $type){
		try{
			$oauth = new FKOAuth1();
			$r = $oauth->fetch_access_token(OAuthRequest::from_request());
		}catch(Exception $e){
			echo "error=". OAuthUtil::urlencode_rfc3986($e->getMessage()); killme();
		}
		echo $r;
		killme();
	}

	api_register_func('api/oauth/request_token', 'api_oauth_request_token', false);
	api_register_func('api/oauth/access_token', 'api_oauth_access_token', false);

function api_share_as_retweet($a, $uid, &$item) {
	$body = trim($item["body"]);

	// Skip if it isn't a pure repeated messages
	// Does it start with a share?
	if (strpos($body, "[share") > 0)
		return(false);

	// Does it end with a share?
	if (strlen($body) > (strrpos($body, "[/share]") + 8))
		return(false);

	$attributes = preg_replace("/\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism","$1",$body);
	// Skip if there is no shared message in there
	if ($body == $attributes)
		return(false);

	$author = "";
	preg_match("/author='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$author = html_entity_decode($matches[1],ENT_QUOTES,'UTF-8');

	preg_match('/author="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$author = $matches[1];

	$profile = "";
	preg_match("/profile='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	preg_match('/profile="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	$avatar = "";
	preg_match("/avatar='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$avatar = $matches[1];

	preg_match('/avatar="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$avatar = $matches[1];

	$shared_body = preg_replace("/\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism","$2",$body);

	if (($shared_body == "") OR ($profile == "") OR ($author == "") OR ($avatar == ""))
		return(false);

	$item["body"] = $shared_body;
	$item["author-name"] = $author;
	$item["author-link"] = $profile;
	$item["author-avatar"] = $avatar;

	return(true);

}

function api_get_nick($profile) {
/* To-Do:
 - remove trailing jung from profile url
 - pump.io check has to check the website
*/

	$nick = "";

	$friendica = preg_replace("=https?://(.*)/profile/(.*)=ism", "$2", $profile);
	if ($friendica != $profile)
		$nick = $friendica;

	if (!$nick == "") {
		$diaspora = preg_replace("=https?://(.*)/u/(.*)=ism", "$2", $profile);
		if ($diaspora != $profile)
			$nick = $diaspora;
	}

	if (!$nick == "") {
		$twitter = preg_replace("=https?://twitter.com/(.*)=ism", "$1", $profile);
		if ($twitter != $profile)
			$nick = $twitter;
	}


	if (!$nick == "") {
		$StatusnetHost = preg_replace("=https?://(.*)/user/(.*)=ism", "$1", $profile);
		if ($StatusnetHost != $profile) {
			$StatusnetUser = preg_replace("=https?://(.*)/user/(.*)=ism", "$2", $profile);
			if ($StatusnetUser != $profile) {
				$UserData = fetch_url("http://".$StatusnetHost."/api/users/show.json?user_id=".$StatusnetUser);
				$user = json_decode($UserData);
				if ($user)
					$nick = $user->screen_name;
			}
		}
	}

	// To-Do: look at the page if its really a pumpio site
	//if (!$nick == "") {
	//	$pumpio = preg_replace("=https?://(.*)/(.*)/=ism", "$2", $profile."/");
        //	if ($pumpio != $profile)
	//		$nick = $pumpio;
		//      <div class="media" id="profile-block" data-profile-id="acct:kabniel@microca.st">

	//}

	if ($nick != "") {
		q("UPDATE unique_contacts SET nick = '%s' WHERE url = '%s'",
			dbesc($nick), dbesc(normalise_link($profile)));
		return($nick);
	}

        return(false);
}

function api_clean_plain_items($Text) {
	$Text = preg_replace_callback("((.*?)\[class=(.*?)\](.*?)\[\/class\])ism","api_cleanup_share",$Text);
	return($Text);
}

function api_cleanup_share($shared) {
        if ($shared[2] != "type-link")
                return($shared[3]);

        if (!preg_match_all("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism",$shared[3], $bookmark))
                return($shared[3]);

        $title = "";
        $link = "";

        if (isset($bookmark[2][0]))
                $title = $bookmark[2][0];

        if (isset($bookmark[1][0]))
                $link = $bookmark[1][0];

	if (strpos($shared[1],$title) !== false)
		$title = "";

	if (strpos($shared[1],$link) !== false)
		$link = "";

        $text = trim($shared[1]);

	//if (strlen($text) < strlen($title))
	if (($text == "") AND ($title != ""))
		$text .= "\n\n".trim($title);

        if ($link != "")
                $text .= "\n".trim($link);

        return(trim($text));
}

function api_best_nickname(&$contacts) {
	$best_contact = array();

	if (count($contact) == 0)
		return;

	foreach ($contacts AS $contact)
		if ($contact["network"] == "") {
			$contact["network"] = "dfrn";
			$best_contact = array($contact);
		}

	if (sizeof($best_contact) == 0)
		foreach ($contacts AS $contact)
			if ($contact["network"] == "dfrn")
				$best_contact = array($contact);

	if (sizeof($best_contact) == 0)
		foreach ($contacts AS $contact)
			if ($contact["network"] == "dspr")
				$best_contact = array($contact);

	if (sizeof($best_contact) == 0)
		foreach ($contacts AS $contact)
			if ($contact["network"] == "stat")
				$best_contact = array($contact);

	if (sizeof($best_contact) == 0)
		foreach ($contacts AS $contact)
			if ($contact["network"] == "pump")
				$best_contact = array($contact);

	if (sizeof($best_contact) == 0)
		foreach ($contacts AS $contact)
			if ($contact["network"] == "twit")
				$best_contact = array($contact);

	if (sizeof($best_contact) == 1)
		$contacts = $best_contact;
	else
		$contacts = array($contacts[0]);
}

/*
Not implemented by now:
favorites
favorites/create
favorites/destroy
statuses/retweets_of_me
friendships/create
friendships/destroy
friendships/exists
friendships/show
account/update_location
account/update_profile_background_image
account/update_profile_image
blocks/create
blocks/destroy

Not implemented in status.net:
statuses/retweeted_to_me
statuses/retweeted_by_me
direct_messages/destroy
account/end_session
account/update_delivery_device
notifications/follow
notifications/leave
blocks/exists
blocks/blocking
lists
*/
