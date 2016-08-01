<?php
/**
 * @file include/NotificationsManager.php
 */
require_once('include/html2plain.php');
require_once("include/datetime.php");
require_once("include/bbcode.php");
require_once("include/dbm.php");

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
				'id' => 'system-tab',
				'accesskey' => 'y',
			),
			array(
				'label' => t('Network'),
				'url'=>'notifications/network',
				'sel'=> (($this->a->argv[1] == 'network') ? 'active' : ''),
				'id' => 'network-tab',
				'accesskey' => 'w',
			),
			array(
				'label' => t('Personal'),
				'url'=>'notifications/personal',
				'sel'=> (($this->a->argv[1] == 'personal') ? 'active' : ''),
				'id' => 'personal-tab',
				'accesskey' => 'r',
			),
			array(
				'label' => t('Home'),
				'url' => 'notifications/home',
				'sel'=> (($this->a->argv[1] == 'home') ? 'active' : ''),
				'id' => 'home-tab',
				'accesskey' => 'h',
			),
			array(
				'label' => t('Introductions'),
				'url' => 'notifications/intros',
				'sel'=> (($this->a->argv[1] == 'intros') ? 'active' : ''),
				'id' => 'intro-tab',
				'accesskey' => 'i',
			),
		);

		return $tabs;
	}

	public function format($notifs, $ident = "") {

		$notif = array();
		$arr = array();

		if (dbm::is_result($notifs)) {

			foreach ($notifs as $it) {
				if($it['unseen'])
					$it['seen'] = ($it['unseen'] > 0 ? false : true);

				switch ($ident) {
					case 'system':
						$default_item_label = 'notify';
						$default_item_link = app::get_baseurl(true).'/notify/view/'. $it['id'];
						$default_item_image = proxy_url($it['photo'], false, PROXY_SIZE_MICRO);
						$default_item_text = strip_tags(bbcode($it['msg']));
						$default_item_when = relative_date($it['date']);
						$default_tpl = $tpl_notify;
						break;

					case 'home':
						$default_item_label = 'comment';
						$default_item_link = app::get_baseurl(true).'/display/'.$it['pguid'];
						$default_item_image = proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO);
						$default_item_text = sprintf( t("%s commented on %s's post"), $it['author-name'], $it['pname']);
						$default_item_when = relative_date($it['created']);
						$default_tpl = $tpl_item_comments;
						break;

					default:
						$default_item_label = (($it['id'] == $it['parent']) ? 'post' : 'comment');
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
						$notif = array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'label' => 'like',
							'link' => app::get_baseurl(true).'/display/'.$it['pguid'],
							'$image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'text' => sprintf( t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'when' => relative_date($it['created']),
							'seen' => $it['seen']
						);
						break;

					case ACTIVITY_DISLIKE:
						$notif = array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'label' => 'dislike',
							'link' => app::get_baseurl(true).'/display/'.$it['pguid'],
							'image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'text' => sprintf( t("%s disliked %s's post"), $it['author-name'], $it['pname']),
							'when' => relative_date($it['created']),
							'seen' => $it['seen']
						);
						break;

					case ACTIVITY_FRIEND:
						$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
						$obj = parse_xml_string($xmlhead.$it['object']);
						$it['fname'] = $obj->title;

						$notif = array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'label' => 'friend',
							'link' => app::get_baseurl(true).'/display/'.$it['pguid'],
							'image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'text' => sprintf( t("%s is now friends with %s"), $it['author-name'], $it['fname']),
							'when' => relative_date($it['created']),
							'seen' => $it['seen']
						);
						break;

					default:
						$notif = array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'label' => $default_item_label,
							'link' => $default_item_link,
							'image' => $default_item_image,
							'text' => $default_item_text,
							'when' => $default_item_when,
							'seen' => $it['seen']
						);
				}

				$arr[] = $notif;
			}
		}

		return $arr;

	}

	private function networkTotal($seen = 0) {
		if($seen === 0)
			$sql_seen = " AND `item`.`unseen` = 1 ";

		$r = q("SELECT COUNT(*) AS `total`
				FROM `item` INNER JOIN `item` AS `pitem` ON `pitem`.`id`=`item`.`parent`
				WHERE `item`.`visible` = 1 AND `pitem`.`parent` != 0 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 0
				$sql_seen",
			intval(local_user())
		);

		if(dbm::is_result($r))
			return $r[0]['total'];

		return 0;
	}

	public function networkNotifs($seen = 0) {
		$ident = 'network';
		$total = $this->networkTotal($seen);

		if($seen === 0)
			$sql_seen = " AND `item`.`unseen` = 1 ";


		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`, `item`.`unseen`,
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object` AS `object`,
				`pitem`.`author-name` AS `pname`, `pitem`.`author-link` AS `plink`, `pitem`.`guid` AS `pguid`
				FROM `item` INNER JOIN `item` AS `pitem` ON `pitem`.`id`=`item`.`parent`
				WHERE `item`.`visible` = 1 AND `pitem`.`parent` != 0 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 0
				$sql_seen
				ORDER BY `item`.`created` DESC",
			intval(local_user())
		);

		if(dbm::is_result($r)) {
			$notifs = $this->format($r, $ident);
			$arr = array (
				'notifications' => $notifs,
				'ident' => $ident,
				'total' => $total,
			);

			return $arr;
		}
	}

	private function systemTotal($seen = 0) {
		if($seen === 0)
			$sql_seen = " AND `seen` = 0 ";

		$r = q("SELECT COUNT(*) AS `total` FROM `notify` WHERE `uid` = %d $sql_seen",
			intval(local_user())
		);

		if(dbm::is_result($r))
			return $r[0]['total'];

		return 0;
	}

	public function systemNotifs($seen = 0) {
		$ident = 'system';
		$total = $this->systemTotal($seen);

		if($seen === 0)
			$sql_seen = " AND `seen` = 0 ";

		$r = q("SELECT * FROM `notify` WHERE `uid` = %d $sql_seen ORDER BY `date` DESC",
			intval(local_user())
		);

		if(dbm::is_result($r)) {
			$notifs = $this->format($r, $ident);
			$arr = array (
				'notifications' => $notifs,
				'ident' => $ident,
				'total' => $total,
			);

			return $arr;
		}
	}

	private function _personal_sql_extra() {
		$myurl = app::get_baseurl(true) . '/profile/'. $this->a->user['nickname'];
		$myurl = substr($myurl,strpos($myurl,'://')+3);
		$myurl = str_replace(array('www.','.'),array('','\\.'),$myurl);
		$diasp_url = str_replace('/profile/','/u/',$myurl);
		$sql_extra .= sprintf(" AND ( `item`.`author-link` regexp '%s' or `item`.`tag` regexp '%s' or `item`.`tag` regexp '%s' ) ",
			dbesc($myurl . '$'),
			dbesc($myurl . '\\]'),
			dbesc($diasp_url . '\\]')
		);

		return $sql_extra;
	}

	private function personalTotal($seen = 0) {
		$sql_extra .= $this->_personal_sql_extra();

		if($seen === 0)
			$sql_seen = " AND `item`.`unseen` = 1 ";

		$r = q("SELECT COUNT(*) AS `total`
				FROM `item` INNER JOIN `item` AS `pitem` ON  `pitem`.`id`=`item`.`parent`
				WHERE `item`.`visible` = 1
				$sql_extra
				$sql_seen
				AND `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 0 " ,
			intval(local_user())
		);

		if(dbm::is_result($r))
			return $r[0]['total'];

		return 0;
	}
	public function personalNotifs($seen = 0) {
		$ident = 'personal';
		$total = 0;
		$sql_extra .= $this->_personal_sql_extra();

		if($seen === 0)
			$sql_seen = " AND `item`.`unseen` = 1 ";

		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`, `item`.`unseen`,
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object` AS `object`, 
				`pitem`.`author-name` AS `pname`, `pitem`.`author-link` AS `plink`, `pitem`.`guid` AS `pguid`, 
				FROM `item` INNER JOIN `item` AS `pitem` ON  `pitem`.`id`=`item`.`parent`
				WHERE `item`.`visible` = 1
				$sql_extra
				$sql_seen
				AND `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 0 
				ORDER BY `item`.`created` DESC" ,
			intval(local_user())
		);

		if(dbm::is_result($r)) {
			$notifs = $this->format($r, $ident);
			$arr = array (
				'notifications' => $notifs,
				'ident' => $ident,
				'total' => $total,
			);

			return $arr;
		}
	}

	private function homeTotal($seen = 0) {
		if($seen === 0)
			$sql_seen = " AND `item`.`unseen` = 1 ";

		$r = q("SELECT COUNT(*) AS `total` FROM `item`
				WHERE ``item`.`visible` = 1 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 1
				$sql_seen",
			intval(local_user())
		);

		if(dbm::is_result($r))
			return $r['total'];

		return 0;
	}

	public function homeNotifs($seen = 0) {
		$ident = 'home';
		$total = $this->homeTotal($seen);

		if($seen === 0)
			$sql_seen = " AND `item`.`unseen` = 1 ";

		$total = $this->homeTotal($seen);

		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`, `item`.`unseen`,
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object` as `object`,
				`pitem`.`author-name` as `pname`, `pitem`.`author-link` as `plink`, `pitem`.`guid` as `pguid`
				FROM `item` INNER JOIN `item` as `pitem` ON `pitem`.`id`=`item`.`parent`
				WHERE `item`.`visible` = 1 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 1
				$sql_seen
				ORDER BY `item`.`created` DESC",
			intval(local_user())
		);

		if(dbm::is_result($r)) {
			$notifs = $this->format($r, $ident);
			$arr = array (
				'notifications' => $notifs,
				'ident' => $ident,
				'total' => $total,
			);

			return $arr;
		}
	}
}
