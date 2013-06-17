<?php /** @file */

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
	private $template = 'conv_item.tpl';
	private $comment_box_template = 'comment_item.tpl';
	private $toplevel = false;
	private $writable = false;
	private $commentable = false;
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
	private $observer = null;
	private $channel = null;

	public function __construct($data) {
		$a = $this->get_app();
				
		$this->data = $data;
		$this->channel = $a->get_channel();
		$this->observer = $a->get_observer();

		$this->toplevel = ($this->get_id() == $this->get_data_value('parent'));


		$this->writable = (((local_user()) && ($this->channel['channel_hash'] === $this->data['owner_xchan'])) ? true : false);
		$this->commentable = $this->writable;

		if(($this->observer) && (! $this->writable)) {
			$this->commentable = can_comment_on_post($this->observer['xchan_hash'],$data);
		}

//		logger('writable: ' . $this->writable);
//		logger('commentable: ' . $this->commentable . ' uid=' . $this->data['uid'] . ' observer=' . $this->observer['xchan_hash']);
//		if(get_config('system','thread_allow') && $a->theme_thread_allow && !$this->is_toplevel())
//			$this->threaded = true;

		// Prepare the children
		if(count($data['children'])) {
			foreach($data['children'] as $item) {

				/*
				 * Only add thos that will be displayed
				 */

				if(! visible_activity($item)) {
					continue;
				}

				$child = new Item($item);
				$this->add_child($child);
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

	public function get_template_data($alike, $dlike, $thread_level=1) {
	
		$t1 = dba_timer();

		$result = array();

		$a        = $this->get_app();
		$observer = $this->observer;
		$item     = $this->get_data();

		$commentww = '';
		$sparkle = '';
		$buttons = '';
		$dropping = false;
		$star = false;
		$isstarred = "unstarred";
		$indent = '';
		$osparkle = '';
		$total_children = $this->count_descendants();

		$conv = $this->get_conversation();

		$lock = ((($item['item_private'] == 1) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid']) 
			|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
			? t('Private Message')
			: false);
		$shareable = ((($conv->get_profile_owner() == local_user()) && ($item['item_private'] != 1)) ? true : false);

		if(local_user() && $observer['xchan_hash'] === $item['author_xchan'])
			$edpost = array($a->get_baseurl($ssl_state)."/editpost/".$item['id'], t("Edit"));
		else
			$edpost = false;

// FIXME - this is wrong.
//		if(($this->get_data_value('uid') == local_user()) || $this->is_visiting())

		if($this->get_data_value('uid') == local_user())
			$dropping = true;

		if($dropping) {
			$drop = array(
				'dropping' => $dropping,
				'delete' => t('Delete'),
			);
		}		

		if($observer_is_pageowner) {		
			$multidrop = array(
				'select' => t('Select'), 
			);
		}

		$filer = (($conv->get_profile_owner() == local_user()) ? t("save to folder") : false);

		$profile_avatar = $item['author']['xchan_photo_m'];
		$profile_link   = chanlink_url($item['author']['xchan_url']);
		$profile_name   = $item['author']['xchan_name'];

		$location = format_location($item);


		$showlike    = ((x($alike,$item['mid'])) ? format_like($alike[$item['mid']],$alike[$item['mid'] . '-l'],'like',$item['mid']) : '');
		$showdislike = ((x($dlike,$item['mid']) && feature_enabled($conv->get_profile_owner(),'dislike'))  
				? format_like($dlike[$item['mid']],$dlike[$item['mid'] . '-l'],'dislike',$item['mid']) : '');

		/*
		 * We should avoid doing this all the time, but it depends on the conversation mode
		 * And the conv mode may change when we change the conv, or it changes its mode
		 * Maybe we should establish a way to be notified about conversation changes
		 */

		$this->check_wall_to_wall();
		
		if($this->is_toplevel()) {
			if($conv->get_profile_owner() == local_user()) {

// FIXME we don't need all this stuff, some can be done in the template

				$star = array(
					'do' => t("add star"),
					'undo' => t("remove star"),
					'toggle' => t("toggle star status"),
					'classdo' => (($item['item_flags'] & ITEM_STARRED) ? "hidden" : ""),
					'classundo' => (($item['item_flags'] & ITEM_STARRED) ? "" : "hidden"),
					'isstarred' => (($item['item_flags'] & ITEM_STARRED) ? "starred" : "unstarred"),
					'starred' =>  t('starred'),
				);

				$tagger = array(
					'tagit' => t("add tag"),
					'classtagger' => "",
				);
			}
		} else {
			$indent = 'comment';
		}

		if($this->is_commentable()) {
			$like = array( t("I like this \x28toggle\x29"), t("like"));
			$dislike = array( t("I don't like this \x28toggle\x29"), t("dislike"));
			if ($shareable)
				$share = array( t('Share this'), t('share'));
		}

		if(strcmp(datetime_convert('UTC','UTC',$item['created']),datetime_convert('UTC','UTC','now - 12 hours')) > 0)
			$indent .= ' shiny';

		$t2 = dba_timer();

		localize_item($item);

		$t3 = dba_timer();

		$body = prepare_body($item,true);

		$t4 = dba_timer();

		$tmp_item = array(
			'template' => $this->get_template(),
			
			'type' => implode("",array_slice(explode("/",$item['verb']),-1)),
			'tags' => array(),
			'body' => $body,
			'text' => strip_tags($body),
			'id' => $this->get_id(),
			'linktitle' => sprintf( t('View %s\'s profile - %s'), $profile_name, $item['author']['xchan_addr']),
			'olinktitle' => sprintf( t('View %s\'s profile - %s'), $this->get_owner_name(), $item['owner']['xchan_addr']),
			'to' => t('to'),
			'wall' => t('Wall-to-Wall'),
			'vwall' => t('via Wall-To-Wall:'),
			'profile_url' => $profile_link,
			'item_photo_menu' => item_photo_menu($item),
			'name' => $profile_name,
			'thumb' => $profile_avatar,
			'osparkle' => $osparkle,
			'sparkle' => $sparkle,
			'title' => $item['title'],
			'ago' => relative_date($item['created']),
			'app' => $item['app'],
			'str_app' => sprintf( t(' from %s'), $item['app']),
			'isotime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'c'),
			'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
			'lock' => $lock,
			'location' => $location,
			'indent' => $indent,
			'owner_url' => $this->get_owner_url(),
			'owner_photo' => $this->get_owner_photo(),
			'owner_name' => $this->get_owner_name(),

// Item toolbar buttons
			'like'      => $like,
			'dislike'   => ((feature_enabled($conv->get_profile_owner(),'dislike')) ? $dislike : ''),
			'share'     => $share,
			'plink'     => get_plink($item),
			'edpost'    => ((feature_enabled($conv->get_profile_owner(),'edit_posts')) ? $edpost : ''),
			'star'      => ((feature_enabled($conv->get_profile_owner(),'star_posts')) ? $star : ''),
			'tagger'    => ((feature_enabled($conv->get_profile_owner(),'commtag')) ? $tagger : ''),
			'filer'     => ((feature_enabled($conv->get_profile_owner(),'filing')) ? $filer : ''),
			'drop'      => $drop,
			'multidrop' => ((feature_enabled($conv->get_profile_owner(),'multi_delete')) ? $multidrop : ''),
// end toolbar buttons

			'showlike' => $showlike,
			'showdislike' => $showdislike,
			'comment' => $this->get_comment_box($indent),
			'previewing' => ($conv->is_preview() ? ' preview ' : ''),
			'wait' => t('Please wait'),
			'thread_level' => $thread_level
		);

		$t5 = dba_timer();

		$arr = array('item' => $item, 'output' => $tmp_item);
		call_hooks('display_item', $arr);

		$result = $arr['output'];

		$result['children'] = array();
		$children = $this->get_children();
		$nb_children = count($children);
		if($nb_children > 0) {
			foreach($children as $child) {
				$result['children'][] = $child->get_template_data($alike, $dlike, $thread_level + 1);
			}
			// Collapse
			if(($nb_children > 2) || ($thread_level > 1)) {
				$result['children'][0]['comment_firstcollapsed'] = true;
				$result['children'][0]['num_comments'] = sprintf( tt('%d comment','%d comments',$total_children),$total_children );
				$result['children'][0]['hide_text'] = t('show more');
				if($thread_level > 1) {
					$result['children'][$nb_children - 1]['comment_lastcollapsed'] = true;
				}
				else {
					$result['children'][$nb_children - 3]['comment_lastcollapsed'] = true;
				}
			}
		}
		
		$result['private'] = $item['private'];
		$result['toplevel'] = ($this->is_toplevel() ? 'toplevel_item' : '');

		if($this->is_threaded()) {
			$result['flatten'] = false;
			$result['threaded'] = true;
		}
		else {
			$result['flatten'] = true;
			$result['threaded'] = false;
		}
		$t6 = dba_timer();

//		profiler($t1,$t2,'t2');
//		profiler($t2,$t3,'t3');
//		profiler($t3,$t4,'t4');
//		profiler($t4,$t5,'t5');
//		profiler($t5,$t6,'t6');
//		profiler($t1,$t6,'item total');

		return $result;
	}
	
	public function get_id() {
		return $this->get_data_value('id');
	}

	public function is_threaded() {
		return $this->threaded;
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
		/*
		 * Only add what will be displayed
		 */

		if(activity_match($item->get_data_value('verb'),ACTIVITY_LIKE) || activity_match($item->get_data_value('verb'),ACTIVITY_DISLIKE)) {
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
	 * Get all our children
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
		$previous_mode = ($this->conversation ? $this->conversation->get_mode() : '');
		
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
	 *      _ value on success
	 *      _ false on failure
	 */
	public function get_data_value($name) {
		if(!isset($this->data[$name])) {
//			logger('[ERROR] Item::get_data_value : Item has no value name "'. $name .'".', LOGGER_DEBUG);
			return false;
		}

		return $this->data[$name];
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
	 * Check if this is writable
	 */
	private function is_writable() {

		return $this->writable;
		
//		$conv = $this->get_conversation();

//		return true;

//		if($conv) {
			// This will allow us to comment on wall-to-wall items owned by our friends
			// and community forums even if somebody else wrote the post.
//			return ($this->writable || ($this->is_visiting() && $conv->get_mode() == 'channel'));
//		}
	}

	private function is_commentable() {
		return $this->commentable;
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

	/**
	 * Get the template for the comment box
	 */
	private function get_comment_box_template() {
		return $this->comment_box_template;
	}

	/**
	 * Get the comment box
	 *
	 * Returns:
	 *      _ The comment box string (empty if no comment box)
	 *      _ false on failure
	 */
	private function get_comment_box($indent) {

		if(!$this->is_toplevel() && !get_config('system','thread_allow')) {
			return '';
		}
		
		$comment_box = '';
		$conv = $this->get_conversation();

		if(! $this->is_commentable())
			return;

		if($conv->is_writable() || $this->is_writable()) {
			$template = get_markup_template($this->get_comment_box_template());

			$a = $this->get_app();

			$qc = ((local_user()) ? get_pconfig(local_user(),'system','qcomment') : null);
			$qcomment = (($qc) ? explode("\n",$qc) : null);

			$comment_box = replace_macros($template,array(
				'$return_path' => '',
				'$threaded' => $this->is_threaded(),
				'$jsreload' => (($conv->get_mode() === 'display') ? $_SESSION['return_url'] : ''),
				'$type' => (($conv->get_mode() === 'channel') ? 'wall-comment' : 'net-comment'),
				'$id' => $this->get_id(),
				'$parent' => $this->get_id(),
				'$qcomment' => $qcomment,
				'$profile_uid' =>  $conv->get_profile_owner(),
				'$mylink' => $this->observer['xchan_url'],
				'$mytitle' => t('This is you'),
				'$myphoto' => $this->observer['xchan_photo_s'],
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
				'$preview' => ((feature_enabled($conv->get_profile_owner(),'preview')) ? t('Preview') : ''),
				'$indent' => $indent,
				'$sourceapp' => get_app()->sourcename
			));
		}

		return $comment_box;
	}

	private function get_redirect_url() {
		return $this->redirect_url;
	}

	/**
	 * Check if we are a wall to wall item and set the relevant properties
	 */
	protected function check_wall_to_wall() {
		$conv = $this->get_conversation();
		$this->wall_to_wall = false;
		$this->owner_url = '';
		$this->owner_photo = '';
		$this->owner_name = '';

		if($conv->get_mode() === 'channel')
			return;
		
		if($this->is_toplevel() && ($this->get_data_value('author_xchan') != $this->get_data_value('owner_xchan'))) {
			$this->owner_url = chanlink_url($this->data['owner']['xchan_url']);
			$this->owner_photo = $this->data['owner']['xchan_photo_m'];
			$this->owner_name = $this->data['owner']['xchan_name'];
			$this->wall_to_wall = true;
		}
	}

	private function is_wall_to_wall() {
		return $this->wall_to_wall;
	}

	private function get_owner_url() {
		return $this->owner_url;
	}

	private function get_owner_photo() {
		return $this->owner_photo;
	}

	private function get_owner_name() {
		return $this->owner_name;
	}

	private function is_visiting() {
		return $this->visiting;
	}




}

