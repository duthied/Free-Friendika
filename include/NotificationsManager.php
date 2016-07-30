<?php
/**
 * @file include/NotificationsManager.php
 */
require_once('include/html2plain.php');
require_once("include/datetime.php");
require_once("include/bbcode.php");

/**
 * @brief Read and write notifications from/to database
 */
class NotificationsManager {
	private $a;

	public function __construct() {
		$this->a = get_app();
	}

	/**
	 * @brief set some extra note properties
	 *
	 * @param array $notes array of note arrays from db
	 * @return array Copy of input array with added properties
	 * 
	 * Set some extra properties to note array from db:
	 *  - timestamp as int in default TZ
	 *  - date_rel : relative date string
	 *  - msg_html: message as html string
	 *  - msg_plain: message as plain text string
	 */
	private function _set_extra($notes) {
		$rets = array();
		foreach($notes as $n) {
			$local_time = datetime_convert('UTC',date_default_timezone_get(),$n['date']);
			$n['timestamp'] = strtotime($local_time);
			$n['date_rel'] = relative_date($n['date']);
				$n['msg_html'] = bbcode($n['msg'], false, false, false, false);
				$n['msg_plain'] = explode("\n",trim(html2plain($n['msg_html'], 0)))[0];

			$rets[] = $n;
		}
		return $rets;
	}


	/**
	 * @brief get all notifications for local_user()
	 *
	 * @param array $filter optional Array "column name"=>value: filter query by columns values
	 * @param string $order optional Space separated list of column to sort by. prepend name with "+" to sort ASC, "-" to sort DESC. Default to "-date"
	 * @param string $limit optional Query limits
	 *
	 * @return array of results or false on errors
	 */
	public function getAll($filter = array(), $order="-date", $limit="") {
		$filter_str = array();
		$filter_sql = "";
		foreach($filter as $column => $value) {
			$filter_str[] = sprintf("`%s` = '%s'", $column, dbesc($value));
		}
		if (count($filter_str)>0) {
			$filter_sql = "AND ".implode(" AND ", $filter_str);
		}

		$aOrder = explode(" ", $order);
		$asOrder = array();
		foreach($aOrder as $o) {
			$dir = "asc";
			if ($o[0]==="-") {
				$dir = "desc";
				$o = substr($o,1);
			}
			if ($o[0]==="+") {
				$dir = "asc";
				$o = substr($o,1);
			}
			$asOrder[] = "$o $dir";
		}
		$order_sql = implode(", ", $asOrder);

		if ($limit!="") $limit = " LIMIT ".$limit;

			$r = q("SELECT * FROM `notify` WHERE `uid` = %d $filter_sql ORDER BY $order_sql $limit",
				intval(local_user())
			);
		if ($r!==false && count($r)>0) return $this->_set_extra($r);
		return false;
	}

	/**
	 * @brief get one note for local_user() by $id value
	 *
	 * @param int $id
	 * @return array note values or null if not found
	 */
	public function getByID($id) {
		$r = q("SELECT * FROM `notify` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($id),
			intval(local_user())
		);
		if($r!==false && count($r)>0) {
			return $this->_set_extra($r)[0];
		}
		return null;
	}

	/**
	 * @brief set seen state of $note of local_user()
	 *
	 * @param array $note
	 * @param bool $seen optional true or false, default true
	 * @return bool true on success, false on errors
	 */
	public function setSeen($note, $seen = true) {
		return q("UPDATE `notify` SET `seen` = %d WHERE ( `link` = '%s' OR ( `parent` != 0 AND `parent` = %d AND `otype` = '%s' )) AND `uid` = %d",
			intval($seen),
			dbesc($note['link']),
			intval($note['parent']),
			dbesc($note['otype']),
			intval(local_user())
		);
	}

	/**
	 * @brief set seen state of all notifications of local_user()
	 *
	 * @param bool $seen optional true or false. default true
	 * @return bool true on success, false on error
	 */
	public function setAllSeen($seen = true) {
		return q("UPDATE `notify` SET `seen` = %d WHERE `uid` = %d",
			intval($seen),
			intval(local_user())
		);
	}

	/**
	 * @brief List of pages for the Notifications TabBar
	 * 
	 * @param app $a The 
	 * @return array with with notifications TabBar data
	 */
	public function getTabs() {
		$tabs = array(
			array(
				'label' => t('System'),
				'url'=>'notifications/system',
				'sel'=> (($this->a->argv[1] == 'system') ? 'active' : ''),
				'accesskey' => 'y',
			),
			array(
				'label' => t('Network'),
				'url'=>'notifications/network',
				'sel'=> (($this->a->argv[1] == 'network') ? 'active' : ''),
				'accesskey' => 'w',
			),
			array(
				'label' => t('Personal'),
				'url'=>'notifications/personal',
				'sel'=> (($this->a->argv[1] == 'personal') ? 'active' : ''),
				'accesskey' => 'r',
			),
			array(
				'label' => t('Home'),
				'url' => 'notifications/home',
				'sel'=> (($this->a->argv[1] == 'home') ? 'active' : ''),
				'accesskey' => 'h',
			),
			array(
				'label' => t('Introductions'),
				'url' => 'notifications/intros',
				'sel'=> (($this->a->argv[1] == 'intros') ? 'active' : ''),
				'accesskey' => 'i',
			),
			/*array(
				'label' => t('Messages'),
				'url' => 'message',
				'sel'=> '',
			),*/ /*while I can have notifications for messages, this tablist is not place for message page link */
		);

		return $tabs;
	}

	public function format($notifs) {

		$notif_content = array();

		// The template files we need in different cases for formatting the content
		$tpl_item_likes = get_markup_template('notifications_likes_item.tpl');
		$tpl_item_dislikes = get_markup_template('notifications_dislikes_item.tpl');
		$tpl_item_friends = get_markup_template('notifications_friends_item.tpl');
		$tpl_item_comments = get_markup_template('notifications_comments_item.tpl');
		$tpl_item_posts = get_markup_template('notifications_posts_item.tpl');
		$tpl_notify = get_markup_template('notify.tpl');

		if (count($notifs['notifications']) > 0) {
	//		switch ($notifs['ident']) {
	//			case 'system':
	//				$default_item_link = app::get_baseurl(true).'/notify/view/'. $it['id'];
	//				$default_item_image = proxy_url($it['photo'], false, PROXY_SIZE_MICRO);
	//				$default_item_text = strip_tags(bbcode($it['msg']));
	//				$default_item_when = relative_date($it['date']);
	//				$default_tpl = $tpl_notify;
	//				break;
	//
	//			case 'home':
	//				$default_item_link = app::get_baseurl(true).'/display/'.$it['pguid'];
	//				$default_item_image = proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO);
	//				$default_item_text = sprintf( t("%s commented on %s's post"), $it['author-name'], $it['pname']);
	//				$default_item_when = relative_date($it['created']);
	//				$default_tpl = $tpl_item_comments;
	//				break;
	//
	//			default:
	//				$default_item_link = app::get_baseurl(true).'/display/'.$it['pguid'];
	//				$default_item_image = proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO);
	//				$default_item_text = (($it['id'] == $it['parent'])
	//							? sprintf( t("%s created a new post"), $it['author-name'])
	//							: sprintf( t("%s commented on %s's post"), $it['author-name'], $it['pname']));
	//				$default_item_when = relative_date($it['created']);
	//				$default_tpl = (($it['id'] == $it['parent']) ? $tpl_item_posts : $tpl_item_comments);
	//
	//		}

			foreach ($notifs['notifications'] as $it) {

				switch ($notifs['ident']) {
					case 'system':
						$default_item_link = app::get_baseurl(true).'/notify/view/'. $it['id'];
						$default_item_image = proxy_url($it['photo'], false, PROXY_SIZE_MICRO);
						$default_item_text = strip_tags(bbcode($it['msg']));
						$default_item_when = relative_date($it['date']);
						$default_tpl = $tpl_notify;
						break;

					case 'home':
						$default_item_link = app::get_baseurl(true).'/display/'.$it['pguid'];
						$default_item_image = proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO);
						$default_item_text = sprintf( t("%s commented on %s's post"), $it['author-name'], $it['pname']);
						$default_item_when = relative_date($it['created']);
						$default_tpl = $tpl_item_comments;
						break;

					default:
						$default_item_link = app::get_baseurl(true).'/display/'.$it['pguid'];
						$default_item_image = proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO);
						$default_item_text = (($it['id'] == $it['parent'])
									? sprintf( t("%s created a new post"), $it['author-name'])
									: sprintf( t("%s commented on %s's post"), $it['author-name'], $it['pname']));
						$default_item_when = relative_date($it['created']);
						$default_tpl = (($it['id'] == $it['parent']) ? $tpl_item_posts : $tpl_item_comments);

				}

				switch($it['verb']){
					case ACTIVITY_LIKE:
						$notif_content[] = replace_macros($tpl_item_likes,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => app::get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'$item_text' => sprintf( t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
						break;

					case ACTIVITY_DISLIKE:
						$notif_content[] = replace_macros($tpl_item_dislikes,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => app::get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'$item_text' => sprintf( t("%s disliked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
						break;

					case ACTIVITY_FRIEND:
						$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
						$obj = parse_xml_string($xmlhead.$it['object']);
						$it['fname'] = $obj->title;

						$notif_content[] = replace_macros($tpl_item_friends,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => app::get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'$item_text' => sprintf( t("%s is now friends with %s"), $it['author-name'], $it['fname']),
							'$item_when' => relative_date($it['created'])
						));
						break;

					default:
						$notif_content[] = replace_macros($default_tpl,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $default_item_link,
							'$item_image' => $default_item_image,
							'$item_text' => $default_item_text,
							'$item_when' => $default_item_when
						));
				}
			}
		}

		return $notif_content;

	}
}
