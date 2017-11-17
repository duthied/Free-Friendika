<?php
/**
 * @file src/Core/Item.php
 */
namespace Friendica\Core;

use Friendica\Core\BaseObject;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Database\DBM;
use Friendica\Protocol\Diaspora;
use dba;

require_once 'include/text.php';
require_once 'boot.php';

/**
 * An item
 */
class Item extends BaseObject
{
	private $data = array();
	private $template = null;
	private $available_templates = array(
		'wall' => 'wall_thread.tpl',
		'wall2wall' => 'wallwall_thread.tpl'
	);
	private $comment_box_template = 'comment_item.tpl';
	private $toplevel = false;
	private $writable = false;
	private $children = array();
	private $parent = null;
	private $conversation = null;
	private $redirect_url = null;
	private $owner_url = '';
	private $owner_photo = '';
	private $owner_name = '';
	private $wall_to_wall = false;
	private $threaded = false;
	private $visiting = false;

	public function __construct($data)
	{
		$a = $this->get_app();

		$this->data = $data;
		$this->setTemplate('wall');
		$this->toplevel = ($this->getId() == $this->getDataValue('parent'));

		if (is_array($_SESSION['remote'])) {
			foreach ($_SESSION['remote'] as $visitor) {
				if ($visitor['cid'] == $this->getDataValue('contact-id')) {
					$this->visiting = true;
					break;
				}
			}
		}

		$this->writable = ($this->getDataValue('writable') || $this->getDataValue('self'));

		$ssl_state = ((local_user()) ? true : false);
		$this->redirect_url = 'redir/' . $this->getDataValue('cid');

		if (Config::get('system', 'thread_allow') && $a->theme_thread_allow && !$this->isToplevel()) {
			$this->threaded = true;
		}

		// Prepare the children
		if (count($data['children'])) {
			foreach ($data['children'] as $item) {
				/*
				 * Only add will be displayed
				 */
				if ($item['network'] === NETWORK_MAIL && local_user() != $item['uid']) {
					continue;
				} elseif (! visible_activity($item)) {
					continue;
				}

				// You can always comment on Diaspora items
				if (($item['network'] == NETWORK_DIASPORA) && (local_user() == $item['uid'])) {
					$item['writable'] = true;
				}

				$item['pagedrop'] = $data['pagedrop'];
				$child = new Item($item);
				$this->addChild($child);
			}
		}
	}

	/**
	 * Get data in a form usable by a conversation template
	 *
	 * Returns:
	 *      _ The data requested on success
	 *      _ false on failure
	 */
	public function getTemplateData($conv_responses, $thread_level = 1)
	{
		require_once "mod/proxy.php";

		$result = array();

		$a = $this->get_app();

		$item = $this->getData();
		$edited = false;
		// If the time between "created" and "edited" differs we add
		// a notice that the post was edited.
		// Note: In some networks reshared items seem to have (sometimes) a difference
		// between creation time and edit time of a second. Thats why we add the notice
		// only if the difference is more than 1 second.
		if (strtotime($item['edited']) - strtotime($item['created']) > 1) {
			$edited = array(
				'label'    => t('This entry was edited'),
				'date'     => datetime_convert('UTC', date_default_timezone_get(), $item['edited'], 'r'),
				'relative' => relative_date($item['edited'])
			);
		}
		$commentww = '';
		$sparkle = '';
		$buttons = '';
		$dropping = false;
		$star = false;
		$ignore = false;
		$isstarred = "unstarred";
		$indent = '';
		$shiny = '';
		$osparkle = '';
		$total_children = $this->countDescendants();

		$conv = $this->getConversation();

		$lock = ((($item['private'] == 1) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid'])
			|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
			? t('Private Message')
			: false);
		$shareable = ((($conv->get_profile_owner() == local_user()) && ($item['private'] != 1)) ? true : false);
		if (local_user() && link_compare($a->contact['url'], $item['author-link'])) {
			if ($item["event-id"] != 0) {
				$edpost = array("events/event/".$item['event-id'], t("Edit"));
			} else {
				$edpost = array("editpost/".$item['id'], t("Edit"));
			}
		} else {
			$edpost = false;
		}

		if (($this->getDataValue('uid') == local_user()) || $this->isVisiting()) {
			$dropping = true;
		}

		$drop = array(
			'dropping' => $dropping,
			'pagedrop' => ((feature_enabled($conv->get_profile_owner(), 'multi_delete')) ? $item['pagedrop'] : ''),
			'select'   => t('Select'),
			'delete'   => t('Delete'),
		);

		$filer = (($conv->get_profile_owner() == local_user()) ? t("save to folder") : false);

		$diff_author    = ((link_compare($item['url'], $item['author-link'])) ? false : true);
		$profile_name   = htmlentities(((strlen($item['author-name'])) && $diff_author) ? $item['author-name'] : $item['name']);
		if ($item['author-link'] && (! $item['author-name'])) {
			$profile_name = $item['author-link'];
		}

		$sp = false;
		$profile_link = best_link_url($item, $sp);
		if ($profile_link === 'mailbox') {
			$profile_link = '';
		}

		if ($sp) {
			$sparkle = ' sparkle';
		} else {
			$profile_link = zrl($profile_link);
		}

		if (!isset($item['author-thumb']) || ($item['author-thumb'] == "")) {
			$author_contact = get_contact_details_by_url($item['author-link'], $conv->get_profile_owner());
			if ($author_contact["thumb"]) {
				$item['author-thumb'] = $author_contact["thumb"];
			} else {
				$item['author-thumb'] = $item['author-avatar'];
			}
		}

		if (!isset($item['owner-thumb']) || ($item['owner-thumb'] == "")) {
			$owner_contact = get_contact_details_by_url($item['owner-link'], $conv->get_profile_owner());
			if ($owner_contact["thumb"]) {
				$item['owner-thumb'] = $owner_contact["thumb"];
			} else {
				$item['owner-thumb'] = $item['owner-avatar'];
			}
		}

		$locate = array('location' => $item['location'], 'coord' => $item['coord'], 'html' => '');
		call_hooks('render_location', $locate);
		$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_dummy($locate));

		$tags=array();
		$hashtags = array();
		$mentions = array();

		/*foreach(explode(',',$item['tag']) as $tag){
			$tag = trim($tag);
			if ($tag!="") {
				$t = bbcode($tag);
				$tags[] = $t;
				if($t[0] == '#')
					$hashtags[] = $t;
				elseif($t[0] == '@')
					$mentions[] = $t;
			}
		}*/

		// process action responses - e.g. like/dislike/attend/agree/whatever
		$response_verbs = array('like');
		if (feature_enabled($conv->get_profile_owner(), 'dislike')) {
			$response_verbs[] = 'dislike';
		}

		if ($item['object-type'] === ACTIVITY_OBJ_EVENT) {
			$response_verbs[] = 'attendyes';
			$response_verbs[] = 'attendno';
			$response_verbs[] = 'attendmaybe';
			if ($conv->is_writable()) {
				$isevent = true;
				$attend = array( t('I will attend'), t('I will not attend'), t('I might attend'));
			}
		}

		$responses = get_responses($conv_responses, $response_verbs, $this, $item);

		foreach ($response_verbs as $value => $verbs) {
			$responses[$verbs]['output']  = ((x($conv_responses[$verbs], $item['uri'])) ? format_like($conv_responses[$verbs][$item['uri']], $conv_responses[$verbs][$item['uri'] . '-l'], $verbs, $item['uri']) : '');
		}

		/*
		 * We should avoid doing this all the time, but it depends on the conversation mode
		 * And the conv mode may change when we change the conv, or it changes its mode
		 * Maybe we should establish a way to be notified about conversation changes
		 */
		$this->checkWallToWall();

		if ($this->isWallToWall() && ($this->getOwnerUrl() == $this->getRedirectUrl())) {
			$osparkle = ' sparkle';
		}

		if ($this->isToplevel()) {
			if ($conv->get_profile_owner() == local_user()) {
				$isstarred = (($item['starred']) ? "starred" : "unstarred");

				$star = array(
					'do'        => t("add star"),
					'undo'      => t("remove star"),
					'toggle'    => t("toggle star status"),
					'classdo'   => (($item['starred']) ? "hidden" : ""),
					'classundo' => (($item['starred']) ? "" : "hidden"),
					'starred'   =>  t('starred'),
				);
				$r = dba::select('thread', array('ignored'), array('uid' => $item['uid'], 'iid' => $item['id']), array('limit' => 1));
				if (DBM::is_result($r)) {
					$ignore = array(
						'do'        => t("ignore thread"),
						'undo'      => t("unignore thread"),
						'toggle'    => t("toggle ignore status"),
						'classdo'   => (($r['ignored']) ? "hidden" : ""),
						'classundo' => (($r['ignored']) ? "" : "hidden"),
						'ignored'   =>  t('ignored'),
					);
				}

				$tagger = '';
				if (feature_enabled($conv->get_profile_owner(), 'commtag')) {
					$tagger = array(
						'add'   => t("add tag"),
						'class' => "",
					);
				}
			}
		} else {
			$indent = 'comment';
		}

		if ($conv->is_writable()) {
			$buttons = array(
				'like' => array( t("I like this \x28toggle\x29"), t("like")),
				'dislike' => ((feature_enabled($conv->get_profile_owner(), 'dislike')) ? array( t("I don't like this \x28toggle\x29"), t("dislike")) : ''),
			);
			if ($shareable) {
				$buttons['share'] = array( t('Share this'), t('share'));
			}
		}

		$comment = $this->getCommentBox($indent);

		if (strcmp(datetime_convert('UTC', 'UTC', $item['created']), datetime_convert('UTC', 'UTC', 'now - 12 hours')) > 0) {
			$shiny = 'shiny';
		}

		localize_item($item);

		$body = prepare_body($item, true);

		list($categories, $folders) = get_cats_and_terms($item);

		if ($a->theme['template_engine'] === 'internal') {
			$body_e       = template_escape($body);
			$text_e       = strip_tags(template_escape($body));
			$name_e       = template_escape($profile_name);
			$title_e      = template_escape($item['title']);
			$location_e   = template_escape($location);
			$owner_name_e = template_escape($this->getOwnerName());
		} else {
			$body_e       = $body;
			$text_e       = strip_tags($body);
			$name_e       = $profile_name;
			$title_e      = $item['title'];
			$location_e   = $location;
			$owner_name_e = $this->getOwnerName();
		}

		// Disable features that aren't available in several networks

		/// @todo Add NETWORK_DIASPORA when it will pass this information
		if (!in_array($item["item_network"], array(NETWORK_DFRN)) && isset($buttons["dislike"])) {
			unset($buttons["dislike"], $isevent);
			$tagger = '';
		}

		if (($item["item_network"] == NETWORK_FEED) && isset($buttons["like"])) {
			unset($buttons["like"]);
		}

		if (($item["item_network"] == NETWORK_MAIL) && isset($buttons["like"])) {
			unset($buttons["like"]);
		}

		$tmp_item = array(
			'template'        => $this->getTemplate(),
			'type'            => implode("", array_slice(explode("/", $item['verb']), -1)),
			'tags'            => $item['tags'],
			'hashtags'        => $item['hashtags'],
			'mentions'        => $item['mentions'],
			'txt_cats'        => t('Categories:'),
			'txt_folders'     => t('Filed under:'),
			'has_cats'        => ((count($categories)) ? 'true' : ''),
			'has_folders'     => ((count($folders)) ? 'true' : ''),
			'categories'      => $categories,
			'folders'         => $folders,
			'body'            => $body_e,
			'text'            => $text_e,
			'id'              => $this->getId(),
			'guid'            => urlencode($item['guid']),
			'isevent'         => $isevent,
			'attend'          => $attend,
			'linktitle'       => sprintf(t('View %s\'s profile @ %s'), $profile_name, ((strlen($item['author-link'])) ? $item['author-link'] : $item['url'])),
			'olinktitle'      => sprintf(t('View %s\'s profile @ %s'), htmlentities($this->getOwnerName()), ((strlen($item['owner-link'])) ? $item['owner-link'] : $item['url'])),
			'to'              => t('to'),
			'via'             => t('via'),
			'wall'            => t('Wall-to-Wall'),
			'vwall'           => t('via Wall-To-Wall:'),
			'profile_url'     => $profile_link,
			'item_photo_menu' => item_photo_menu($item),
			'name'            => $name_e,
			'thumb'           => $a->remove_baseurl(proxy_url($item['author-thumb'], false, PROXY_SIZE_THUMB)),
			'osparkle'        => $osparkle,
			'sparkle'         => $sparkle,
			'title'           => $title_e,
			'localtime'       => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
			'ago'             => (($item['app']) ? sprintf(t('%s from %s'), relative_date($item['created']), $item['app']) : relative_date($item['created'])),
			'app'             => $item['app'],
			'created'         => relative_date($item['created']),
			'lock'            => $lock,
			'location'        => $location_e,
			'indent'          => $indent,
			'shiny'           => $shiny,
			'owner_url'       => $this->getOwnerUrl(),
			'owner_photo'     => $a->remove_baseurl(proxy_url($item['owner-thumb'], false, PROXY_SIZE_THUMB)),
			'owner_name'      => htmlentities($owner_name_e),
			'plink'           => get_plink($item),
			'edpost'          => ((feature_enabled($conv->get_profile_owner(), 'edit_posts')) ? $edpost : ''),
			'isstarred'       => $isstarred,
			'star'            => ((feature_enabled($conv->get_profile_owner(), 'star_posts')) ? $star : ''),
			'ignore'          => ((feature_enabled($conv->get_profile_owner(), 'ignore_posts')) ? $ignore : ''),
			'tagger'          => $tagger,
			'filer'           => ((feature_enabled($conv->get_profile_owner(), 'filing')) ? $filer : ''),
			'drop'            => $drop,
			'vote'            => $buttons,
			'like'            => $responses['like']['output'],
			'dislike'         => $responses['dislike']['output'],
			'responses'       => $responses,
			'switchcomment'   => t('Comment'),
			'comment'         => $comment,
			'previewing'      => ($conv->is_preview() ? ' preview ' : ''),
			'wait'            => t('Please wait'),
			'thread_level'    => $thread_level,
			'edited'          => $edited,
			'network'         => $item["item_network"],
			'network_name'    => network_to_name($item['item_network'], $profile_link),
			'received'        => $item['received'],
			'commented'       => $item['commented'],
			'created_date'    => $item['created'],
		);

		$arr = array('item' => $item, 'output' => $tmp_item);
		call_hooks('display_item', $arr);

		$result = $arr['output'];

		$result['children'] = array();
		$children = $this->getChildren();
		$nb_children = count($children);
		if ($nb_children > 0) {
			foreach ($children as $child) {
				$result['children'][] = $child->getTemplateData($conv_responses, $thread_level + 1);
			}
			// Collapse
			if (($nb_children > 2) || ($thread_level > 1)) {
				$result['children'][0]['comment_firstcollapsed'] = true;
				$result['children'][0]['num_comments'] = sprintf(tt('%d comment', '%d comments', $total_children), $total_children);
				$result['children'][0]['hidden_comments_num'] = $total_children;
				$result['children'][0]['hidden_comments_text'] = tt('comment', 'comments', $total_children);
				$result['children'][0]['hide_text'] = t('show more');
				if ($thread_level > 1) {
					$result['children'][$nb_children - 1]['comment_lastcollapsed'] = true;
				} else {
					$result['children'][$nb_children - 3]['comment_lastcollapsed'] = true;
				}
			}
		}

		if ($this->isToplevel()) {
			$result['total_comments_num'] = "$total_children";
			$result['total_comments_text'] = tt('comment', 'comments', $total_children);
		}

		$result['private'] = $item['private'];
		$result['toplevel'] = ($this->isToplevel() ? 'toplevel_item' : '');

		if ($this->isThreaded()) {
			$result['flatten'] = false;
			$result['threaded'] = true;
		} else {
			$result['flatten'] = true;
			$result['threaded'] = false;
		}

		return $result;
	}

	public function getId()
	{
		return $this->getDataValue('id');
	}

	public function isThreaded()
	{
		return $this->threaded;
	}

	/**
	 * Add a child item
	 */
	public function addChild(Item $item)
	{
		$item_id = $item->getId();
		if (!$item_id) {
			logger('[ERROR] Item::addChild : Item has no ID!!', LOGGER_DEBUG);
			return false;
		} elseif ($this->getChild($item->getId())) {
			logger('[WARN] Item::addChild : Item already exists ('. $item->getId() .').', LOGGER_DEBUG);
			return false;
		}
		/*
		 * Only add what will be displayed
		 */
		if ($item->getDataValue('network') === NETWORK_MAIL && local_user() != $item->getDataValue('uid')) {
			return false;
		} elseif (activity_match($item->getDataValue('verb'), ACTIVITY_LIKE) || activity_match($item->getDataValue('verb'), ACTIVITY_DISLIKE)) {
			return false;
		}

		$item->setParent($this);
		$this->children[] = $item;

		return end($this->children);
	}

	/**
	 * Get a child by its ID
	 */
	public function getChild($id)
	{
		foreach ($this->getChildren() as $child) {
			if ($child->getId() == $id) {
				return $child;
			}
		}

		return null;
	}

	/**
	 * Get all ou children
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * Set our parent
	 */
	protected function setParent($item)
	{
		$parent = $this->getParent();
		if ($parent) {
			$parent->removeChild($this);
		}

		$this->parent = $item;
		$this->setConversation($item->getConversation());
	}

	/**
	 * Remove our parent
	 */
	protected function removeParent()
	{
		$this->parent = null;
		$this->conversation = null;
	}

	/**
	 * Remove a child
	 */
	public function removeChild($item)
	{
		$id = $item->getId();
		foreach ($this->getChildren() as $key => $child) {
			if ($child->getId() == $id) {
				$child->removeParent();
				unset($this->children[$key]);
				// Reindex the array, in order to make sure there won't be any trouble on loops using count()
				$this->children = array_values($this->children);
				return true;
			}
		}
		logger('[WARN] Item::removeChild : Item is not a child ('. $id .').', LOGGER_DEBUG);
		return false;
	}

	/**
	 * Get parent item
	 */
	protected function getParent()
	{
		return $this->parent;
	}

	/**
	 * set conversation
	 */
	public function setConversation($conv)
	{
		$previous_mode = ($this->conversation ? $this->conversation->get_mode() : '');

		$this->conversation = $conv;

		// Set it on our children too
		foreach ($this->getChildren() as $child) {
			$child->setConversation($conv);
		}
	}

	/**
	 * get conversation
	 */
	public function getConversation()
	{
		return $this->conversation;
	}

	/**
	 * Get raw data
	 *
	 * We shouldn't need this
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Get a data value
	 *
	 * Returns:
	 *      _ value on success
	 *      _ false on failure
	 */
	public function getDataValue($name)
	{
		if (!isset($this->data[$name])) {
			// logger('[ERROR] Item::getDataValue : Item has no value name "'. $name .'".', LOGGER_DEBUG);
			return false;
		}

		return $this->data[$name];
	}

	/**
	 * Set template
	 */
	private function setTemplate($name)
	{
		if (!x($this->available_templates, $name)) {
			logger('[ERROR] Item::setTemplate : Template not available ("'. $name .'").', LOGGER_DEBUG);
			return false;
		}

		$this->template = $this->available_templates[$name];
	}

	/**
	 * Get template
	 */
	private function getTemplate()
	{
		return $this->template;
	}

	/**
	 * Check if this is a toplevel post
	 */
	private function isToplevel()
	{
		return $this->toplevel;
	}

	/**
	 * Check if this is writable
	 */
	private function isWritable()
	{
		$conv = $this->getConversation();

		if ($conv) {
			// This will allow us to comment on wall-to-wall items owned by our friends
			// and community forums even if somebody else wrote the post.

			// bug #517 - this fixes for conversation owner
			if ($conv->get_mode() == 'profile' && $conv->get_profile_owner() == local_user()) {
				return true;
			}

			// this fixes for visitors
			return ($this->writable || ($this->isVisiting() && $conv->get_mode() == 'profile'));
		}
		return $this->writable;
	}

	/**
	 * Count the total of our descendants
	 */
	private function countDescendants()
	{
		$children = $this->getChildren();
		$total = count($children);
		if ($total > 0) {
			foreach ($children as $child) {
				$total += $child->countDescendants();
			}
		}

		return $total;
	}

	/**
	 * Get the template for the comment box
	 */
	private function getCommentBoxTemplate()
	{
		return $this->comment_box_template;
	}

	/**
	 * Get the comment box
	 *
	 * Returns:
	 *      _ The comment box string (empty if no comment box)
	 *      _ false on failure
	 */
	private function getCommentBox($indent)
	{
		$a = $this->get_app();
		if (!$this->isToplevel() && !(Config::get('system', 'thread_allow') && $a->theme_thread_allow)) {
			return '';
		}

		$comment_box = '';
		$conv = $this->getConversation();
		$template = get_markup_template($this->getCommentBoxTemplate());
		$ww = '';
		if (($conv->get_mode() === 'network') && $this->isWallToWall()) {
			$ww = 'ww';
		}

		if ($conv->is_writable() && $this->isWritable()) {
			$qc = $qcomment =  null;

			/*
			 * Hmmm, code depending on the presence of a particular plugin?
			 * This should be better if done by a hook
			 */
			if (in_array('qcomment', $a->plugins)) {
				$qc = ((local_user()) ? PConfig::get(local_user(), 'qcomment', 'words') : null);
				$qcomment = (($qc) ? explode("\n", $qc) : null);
			}

			$comment_box = replace_macros(
				$template,
				array(
				'$return_path' => $a->query_string,
				'$threaded'    => $this->isThreaded(),
				// '$jsreload'    => (($conv->get_mode() === 'display') ? $_SESSION['return_url'] : ''),
				'$jsreload'    => '',
				'$type'        => (($conv->get_mode() === 'profile') ? 'wall-comment' : 'net-comment'),
				'$id'          => $this->getId(),
				'$parent'      => $this->getId(),
				'$qcomment'    => $qcomment,
				'$profile_uid' =>  $conv->get_profile_owner(),
				'$mylink'      => $a->remove_baseurl($a->contact['url']),
				'$mytitle'     => t('This is you'),
				'$myphoto'     => $a->remove_baseurl($a->contact['thumb']),
				'$comment'     => t('Comment'),
				'$submit'      => t('Submit'),
				'$edbold'      => t('Bold'),
				'$editalic'    => t('Italic'),
				'$eduline'     => t('Underline'),
				'$edquote'     => t('Quote'),
				'$edcode'      => t('Code'),
				'$edimg'       => t('Image'),
				'$edurl'       => t('Link'),
				'$edvideo'     => t('Video'),
				'$preview'     => ((feature_enabled($conv->get_profile_owner(), 'preview')) ? t('Preview') : ''),
				'$indent'      => $indent,
				'$sourceapp'   => t($a->sourcename),
				'$ww'          => (($conv->get_mode() === 'network') ? $ww : ''),
				'$rand_num'    => random_digits(12))
			);
		}

		return $comment_box;
	}

	private function getRedirectUrl()
	{
		return $this->redirect_url;
	}

	/**
	 * Check if we are a wall to wall item and set the relevant properties
	 */
	protected function checkWallToWall()
	{
		$a = $this->get_app();
		$conv = $this->getConversation();
		$this->wall_to_wall = false;

		if ($this->isToplevel()) {
			if ($conv->get_mode() !== 'profile') {
				if ($this->getDataValue('wall') && !$this->getDataValue('self')) {
					// On the network page, I am the owner. On the display page it will be the profile owner.
					// This will have been stored in $a->page_contact by our calling page.
					// Put this person as the wall owner of the wall-to-wall notice.

					$this->owner_url = zrl($a->page_contact['url']);
					$this->owner_photo = $a->page_contact['thumb'];
					$this->owner_name = $a->page_contact['name'];
					$this->wall_to_wall = true;
				} elseif ($this->getDataValue('owner-link')) {
					$owner_linkmatch = (($this->getDataValue('owner-link')) && link_compare($this->getDataValue('owner-link'), $this->getDataValue('author-link')));
					$alias_linkmatch = (($this->getDataValue('alias')) && link_compare($this->getDataValue('alias'), $this->getDataValue('author-link')));
					$owner_namematch = (($this->getDataValue('owner-name')) && $this->getDataValue('owner-name') == $this->getDataValue('author-name'));

					if ((! $owner_linkmatch) && (! $alias_linkmatch) && (! $owner_namematch)) {
						// The author url doesn't match the owner (typically the contact)
						// and also doesn't match the contact alias.
						// The name match is a hack to catch several weird cases where URLs are
						// all over the park. It can be tricked, but this prevents you from
						// seeing "Bob Smith to Bob Smith via Wall-to-wall" and you know darn
						// well that it's the same Bob Smith.

						// But it could be somebody else with the same name. It just isn't highly likely.


						$this->owner_photo = $this->getDataValue('owner-avatar');
						$this->owner_name = $this->getDataValue('owner-name');
						$this->wall_to_wall = true;
						// If it is our contact, use a friendly redirect link
						if ((link_compare($this->getDataValue('owner-link'), $this->getDataValue('url')))
							&& ($this->getDataValue('network') === NETWORK_DFRN)
						) {
							$this->owner_url = $this->getRedirectUrl();
						} else {
							$this->owner_url = zrl($this->getDataValue('owner-link'));
						}
					}
				}
			}
		}

		if (!$this->wall_to_wall) {
			$this->setTemplate('wall');
			$this->owner_url = '';
			$this->owner_photo = '';
			$this->owner_name = '';
		}
	}

	private function isWallToWall()
	{
		return $this->wall_to_wall;
	}

	private function getOwnerUrl()
	{
		return $this->owner_url;
	}

	private function getOwnerPhoto()
	{
		return $this->owner_photo;
	}

	private function getOwnerName()
	{
		return $this->owner_name;
	}

	private function isVisiting()
	{
		return $this->visiting;
	}
}
