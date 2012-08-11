<?php
if(class_exists('Item'))
	return;

require_once('object/BaseObject.php');
require_once('include/text.php');
require_once('boot.php');

/**
 * An item
 */
class Item extends BaseObject {
	private $data = array();
	private $template = null;
	private $available_templates = array(
		'wall' => 'wall_thread.tpl',
		'wall2wall' => 'wallwall_thread.tpl'
	);
	private $toplevel = false;
	private $writeable = false;
	private $children = array();
	private $parent = null;
	private $conversation = null;

	public function __construct($data) {
		$this->data = $data;
		$this->set_template('wall');
		$this->toplevel = ($this->get_id() == $this->get_data_value('parent'));
		$this->writeable = ($this->get_data_value('writeable') || $this->get_data_value('self'));

		// Prepare the children
		foreach($data['children'] as $item) {
			$child = new Item($item);
			$this->add_child($child);
		}
	}

	/**
	 * Get data in a form usable by a conversation template
	 *
	 * Returns:
	 * 		_ The data requested on success
	 * 		_ false on failure
	 */
	public function get_template_data($cmnt_tpl, $alike, $dlike) {
		$result = array();

		$a = $this->get_app();

		$item = $this->get_data();

		$comment = '';
		$commentww = '';
		$sparkle = '';
		$owner_url = $owner_photo = $owner_name = '';
		$buttons = '';
		$dropping = false;
		$star = false;
		$isstarred = "unstarred";
		$indent = '';
		$osparkle = '';
		$lastcollapsed = false;
		$firstcollapsed = false;
		$total_children = $this->count_descendants();

		$conv = $this->get_conversation();

		$show_comment_box = ((($conv->is_writeable()) && ($this->is_writeable())) ? true : false);
		$lock = ((($item['private'] == 1) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid']) 
			|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
			? t('Private Message')
			: false);
		$redirect_url = $a->get_baseurl($ssl_state) . '/redir/' . $item['cid'] ;
		$shareable = ((($conv->get_profile_owner() == local_user()) && ($item['private'] != 1)) ? true : false);
		if(local_user() && link_compare($a->contact['url'],$item['author-link']))
			$edpost = array($a->get_baseurl($ssl_state)."/editpost/".$item['id'], t("Edit"));
		else
			$edpost = false;
		if((intval($item['contact-id']) && $item['contact-id'] == remote_user()) || ($item['uid'] == local_user()))
			$dropping = true;

		$drop = array(
			'dropping' => $dropping,
			'select' => t('Select'), 
			'delete' => t('Delete'),
		);
		
		$filer = (($conv->get_profile_owner() == local_user()) ? t("save to folder") : false);

		$diff_author    = ((link_compare($item['url'],$item['author-link'])) ? false : true);
		$profile_name   = (((strlen($item['author-name']))   && $diff_author) ? $item['author-name']   : $item['name']);
		if($item['author-link'] && (! $item['author-name']))
			$profile_name = $item['author-link'];

		$sp = false;
		$profile_link = best_link_url($item,$sp);
		if($profile_link === 'mailbox')
			$profile_link = '';
		if($sp)
			$sparkle = ' sparkle';
		else
			$profile_link = zrl($profile_link);					

		$normalised = normalise_link((strlen($item['author-link'])) ? $item['author-link'] : $item['url']);
		if(($normalised != 'mailbox') && (x($a->contacts,$normalised)))
			$profile_avatar = $a->contacts[$normalised]['thumb'];
		else
			$profile_avatar = (((strlen($item['author-avatar'])) && $diff_author) ? $item['author-avatar'] : $a->get_cached_avatar_image($this->get_data_value('thumb')));

		$locate = array('location' => $item['location'], 'coord' => $item['coord'], 'html' => '');
		call_hooks('render_location',$locate);
		$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_google($locate));

		$tags=array();
		foreach(explode(',',$item['tag']) as $tag){
			$tag = trim($tag);
			if ($tag!="") $tags[] = bbcode($tag);
		}
		
		like_puller($a,$item,$alike,'like');
		like_puller($a,$item,$dlike,'dislike');

		$like    = ((x($alike,$item['uri'])) ? format_like($alike[$item['uri']],$alike[$item['uri'] . '-l'],'like',$item['uri']) : '');
		$dislike = ((x($dlike,$item['uri'])) ? format_like($dlike[$item['uri']],$dlike[$item['uri'] . '-l'],'dislike',$item['uri']) : '');

		if($this->is_toplevel()) {
			if((! $item['self']) && ($conv->get_mode() !== 'profile')) {
				if($item['wall']) {

					// On the network page, I am the owner. On the display page it will be the profile owner.
					// This will have been stored in $a->page_contact by our calling page.
					// Put this person as the wall owner of the wall-to-wall notice.

					$owner_url = zrl($a->page_contact['url']);
					$owner_photo = $a->page_contact['thumb'];
					$owner_name = $a->page_contact['name'];
					$this->set_template('wall2wall');
					$commentww = 'ww';	
				}
			}
			else if($item['owner-link']) {

				$owner_linkmatch = (($item['owner-link']) && link_compare($item['owner-link'],$item['author-link']));
				$alias_linkmatch = (($item['alias']) && link_compare($item['alias'],$item['author-link']));
				$owner_namematch = (($item['owner-name']) && $item['owner-name'] == $item['author-name']);
				if((! $owner_linkmatch) && (! $alias_linkmatch) && (! $owner_namematch)) {

					// The author url doesn't match the owner (typically the contact)
					// and also doesn't match the contact alias. 
					// The name match is a hack to catch several weird cases where URLs are 
					// all over the park. It can be tricked, but this prevents you from
					// seeing "Bob Smith to Bob Smith via Wall-to-wall" and you know darn
					// well that it's the same Bob Smith. 

					// But it could be somebody else with the same name. It just isn't highly likely. 
					

					$owner_url = $item['owner-link'];
					$owner_photo = $item['owner-avatar'];
					$owner_name = $item['owner-name'];
					$this->set_template('wall2wall');
					$commentww = 'ww';
					// If it is our contact, use a friendly redirect link
					if((link_compare($item['owner-link'],$item['url'])) 
						&& ($item['network'] === NETWORK_DFRN)) {
						$owner_url = $redirect_url;
						$osparkle = ' sparkle';
					}
					else
						$owner_url = zrl($owner_url);
				}
			}
			if($conv->get_profile_owner() == local_user()) {
				$isstarred = (($item['starred']) ? "starred" : "unstarred");

				$star = array(
					'do' => t("add star"),
					'undo' => t("remove star"),
					'toggle' => t("toggle star status"),
					'classdo' => (($item['starred']) ? "hidden" : ""),
					'classundo' => (($item['starred']) ? "" : "hidden"),
					'starred' =>  t('starred'),
					'tagger' => t("add tag"),
					'classtagger' => "",
				);
			}
		} else {
			$indent = 'comment';
			// Collapse comments
			if(($nb_items > 2) || ($thread_level > 2)) {
				if($items_seen == 1) {
					$firstcollapsed = true;
				}
				if($thread_level > 2) {
					if($items_seen == $nb_items)
						$lastcollapsed = true;
				}
				else if($items_seen == ($nb_items - 2)) {
					$lastcollapsed = true;
				}
			}
		}

		if($conv->is_writeable()) {
			$buttons = array(
				'like' => array( t("I like this \x28toggle\x29"), t("like")),
				'dislike' => array( t("I don't like this \x28toggle\x29"), t("dislike")),
			);
			if ($shareable) $buttons['share'] = array( t('Share this'), t('share'));


			if($show_comment_box) {
				$qc = $qcomment =  null;

				if(in_array('qcomment',$a->plugins)) {
					$qc = ((local_user()) ? get_pconfig(local_user(),'qcomment','words') : null);
					$qcomment = (($qc) ? explode("\n",$qc) : null);
				}
				$comment = replace_macros($cmnt_tpl,array(
					'$return_path' => '', 
					'$jsreload' => (($conv->get_mode() === 'display') ? $_SESSION['return_url'] : ''),
					'$type' => (($conv->get_mode() === 'profile') ? 'wall-comment' : 'net-comment'),
					'$id' => $item['item_id'],
					'$parent' => $item['item_id'],
					'$qcomment' => $qcomment,
					'$profile_uid' =>  $conv->get_profile_owner(),
					'$mylink' => $a->contact['url'],
					'$mytitle' => t('This is you'),
					'$myphoto' => $a->contact['thumb'],
					'$comment' => t('Comment'),
					'$submit' => t('Submit'),
					'$edbold' => t('Bold'),
					'$editalic' => t('Italic'),
					'$eduline' => t('Underline'),
					'$edquote' => t('Quote'),
					'$edcode' => t('Code'),
					'$edimg' => t('Image'),
					'$edurl' => t('Link'),
					'$edvideo' => t('Video'),
					'$preview' => t('Preview'),
					'$sourceapp' => t($a->sourcename),
					'$ww' => (($conv->get_mode() === 'network') ? $commentww : '')
				));
			}
		}

		if(strcmp(datetime_convert('UTC','UTC',$item['created']),datetime_convert('UTC','UTC','now - 12 hours')) > 0)
			$indent .= ' shiny';

		localize_item($item);

		$body = prepare_body($item,true);

		$tmp_item = array(
			// collapse comments in template. I don't like this much...
			'comment_firstcollapsed' => $firstcollapsed,
			'comment_lastcollapsed' => $lastcollapsed,
			// template to use to render item (wall, walltowall, search)
			'template' => $this->get_template(),
			
			'type' => implode("",array_slice(explode("/",$item['verb']),-1)),
			'tags' => $tags,
			'body' => template_escape($body),
			'text' => strip_tags(template_escape($body)),
			'id' => $item['item_id'],
			'linktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, ((strlen($item['author-link'])) ? $item['author-link'] : $item['url'])),
			'olinktitle' => sprintf( t('View %s\'s profile @ %s'), $owner_name, ((strlen($item['owner-link'])) ? $item['owner-link'] : $item['url'])),
			'to' => t('to'),
			'wall' => t('Wall-to-Wall'),
			'vwall' => t('via Wall-To-Wall:'),
			'profile_url' => $profile_link,
			'item_photo_menu' => item_photo_menu($item),
			'name' => template_escape($profile_name),
			'thumb' => $profile_avatar,
			'osparkle' => $osparkle,
			'sparkle' => $sparkle,
			'title' => template_escape($item['title']),
			'ago' => (($item['app']) ? sprintf( t('%s from %s'),relative_date($item['created']),$item['app']) : relative_date($item['created'])),
			'lock' => $lock,
			'location' => template_escape($location),
			'indent' => $indent,
			'owner_url' => $owner_url,
			'owner_photo' => $owner_photo,
			'owner_name' => template_escape($owner_name),
			'plink' => get_plink($item),
			'edpost' => $edpost,
			'isstarred' => $isstarred,
			'star' => $star,
			'filer' => $filer,
			'drop' => $drop,
			'vote' => $buttons,
			'like' => $like,
			'dislike' => $dislike,
			'comment' => $comment,
			'previewing' => $previewing,
			'wait' => t('Please wait'),
		);

		$arr = array('item' => $item, 'output' => $tmp_item);
		call_hooks('display_item', $arr);

		$item_result = $arr['output'];
		if($firstcollapsed) {
			$item_result['num_comments'] = sprintf( tt('%d comment','%d comments',$total_children),$total_children );
			$item_result['hide_text'] = t('show more');
		}

		$item_result['children'] = array();
		if(count($item['children'])) {
			$item_result['children'] = prepare_threads_body($a, $item['children'], $cmnt_tpl, $conv->is_writeable(), $conv->get_mode(), $conv->get_profile_owner(), $alike, $dlike, ($thread_level + 1));
		}
		$item_result['private'] = $item['private'];
		$item_result['toplevel'] = ($this->is_toplevel() ? 'toplevel_item' : '');

		if(get_config('system','thread_allow')) {
			$item_result['flatten'] = false;
			$item_result['threaded'] = true;
		}
		else {
			$item_result['flatten'] = true;
			$item_result['threaded'] = false;
			if(!$htis->is_toplevel()) {
				$item_result['comment'] = false;
			}
		}
		
		$result = $item_result;

		return $result;
	}
	
	public function get_id() {
		return $this->get_data_value('id');
	}

	/**
	 * Add a child item
	 */
	public function add_child($item) {
		$item_id = $item->get_id();
		if(!$item_id) {
			logger('[ERROR] Item::add_child : Item has no ID!!', LOGGER_DEBUG);
			return false;
		}
		if($this->get_child($item->get_id())) {
			logger('[WARN] Item::add_child : Item already exists ('. $item->get_id() .').', LOGGER_DEBUG);
			return false;
		}
		$item->set_parent($this);
		$this->children[] = $item;
		return end($this->children);
	}

	/**
	 * Get a child by its ID
	 */
	public function get_child($id) {
		foreach($this->get_children() as $child) {
			if($child->get_id() == $id)
				return $child;
		}
		return null;
	}

	/**
	 * Get all ou children
	 */
	public function get_children() {
		return $this->children;
	}

	/**
	 * Set our parent
	 */
	protected function set_parent($item) {
		$parent = $this->get_parent();
		if($parent) {
			$parent->remove_child($this);
		}
		$this->parent = $item;
		$this->set_conversation($item->get_conversation());
	}

	/**
	 * Remove our parent
	 */
	protected function remove_parent() {
		$this->parent = null;
		$this->conversation = null;
	}

	/**
	 * Remove a child
	 */
	public function remove_child($item) {
		$id = $item->get_id();
		foreach($this->get_children() as $key => $child) {
			if($child->get_id() == $id) {
				$child->remove_parent();
				unset($this->children[$key]);
				// Reindex the array, in order to make sure there won't be any trouble on loops using count()
				$this->children = array_values($this->children);
				return true;
			}
		}
		logger('[WARN] Item::remove_child : Item is not a child ('. $id .').', LOGGER_DEBUG);
		return false;
	}

	/**
	 * Get parent item
	 */
	protected function get_parent() {
		return $this->parent;
	}

	/**
	 * set conversation
	 */
	public function set_conversation($conv) {
		$this->conversation = $conv;

		// Set it on our children too
		foreach($this->get_children() as $child)
			$child->set_conversation($conv);
	}

	/**
	 * get conversation
	 */
	public function get_conversation() {
		return $this->conversation;
	}

	/**
	 * Get raw data
	 *
	 * We shouldn't need this
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Get a data value
	 *
	 * Returns:
	 * 		_ value on success
	 * 		_ false on failure
	 */
	public function get_data_value($name) {
		if(!x($this->data, $name)) {
			logger('[ERROR] Item::get_data_value : Item has no value name "'. $name .'".', LOGGER_DEBUG);
			return false;
		}

		return $this->data[$name];
	}

	/**
	 * Set template
	 */
	private function set_template($name) {
		if(!x($this->available_templates, $name)) {
			logger('[ERROR] Item::set_template : Template not available ("'. $name .'").', LOGGER_DEBUG);
			return false;
		}
		$this->template = $this->available_templates[$name];
	}

	/**
	 * Get template
	 */
	private function get_template() {
		return $this->template;
	}

	/**
	 * Check if this is a toplevel post
	 */
	private function is_toplevel() {
		return $this->toplevel;
	}

	/**
	 * Check if this is writeable
	 */
	private function is_writeable() {
		return $this->writeable;
	}

	/**
	 * Count the total of our descendants
	 */
	private function count_descendants() {
		$children = $this->get_children();
		$total = count($children);
		if($total > 0) {
			foreach($children as $child) {
				$total += $child->count_descendants();
			}
		}
		return $total;
	}
}
?>
