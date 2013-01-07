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
	private $template = 'conv_item.tpl';
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
	private $observer = null;
	private $channel = null;

	public function __construct($data) {
		$a = $this->get_app();
				
		$this->data = $data;
		$this->channel = $a->get_channel();
		$this->observer = $a->get_observer();

		$this->toplevel = ($this->get_id() == $this->get_data_value('parent'));

//		if(is_array($_SESSION['remote'])) {
//			foreach($_SESSION['remote'] as $visitor) {
//				if($visitor['cid'] == $this->get_data_value('contact-id')) {
//					$this->visiting = true;
//					break;
//				}
//			}
//		}

// fixme		
		$this->writable = ($this->get_data_value('writable') || $this->get_data_value('self'));
// FIXME - base this on observer permissions
		$this->writable = ((local_user() && $channel['channel_hash'] === $item['owner_xchan']) ? true : false);



		if(get_config('system','thread_allow') && $a->theme_thread_allow && !$this->is_toplevel())
			$this->threaded = true;

		// Prepare the children
		if(count($data['children'])) {
			foreach($data['children'] as $item) {
				/*
				 * Only add will be displayed
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

		$lock = ((($item['private'] == 1) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid']) 
			|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
			? t('Private Message')
			: false);
		$shareable = ((($conv->get_profile_owner() == local_user()) && ($item['private'] != 1)) ? true : false);

		if(local_user() && link_compare($a->contact['url'],$item['author-link']))
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

		$diff_author    = ((link_compare($item['url'],$item['author-link'])) ? false : true);
		$profile_name   = (((strlen($item['author-name']))   && $diff_author) ? $item['author-name']   : $item['name']);

		$profile_avatar = $item['author']['xchan_photo_m'];
		$profile_link = $a->get_baseurl() . '/chanview/?f=&url=' . $item['author']['xchan_url'];
		$profile_name = $item['author']['xchan_name'];

//		if($item['author-link'] && (! $item['author-name']))
//			$profile_name = $item['author-link'];


		$profile_avatar = $item['author']['xchan_photo_m'];

		$locate = array('location' => $item['location'], 'coord' => $item['coord'], 'html' => '');
		call_hooks('render_location',$locate);
		$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_google($locate));

		$tags=array();
		foreach(explode(',',$item['tag']) as $tag){
			$tag = trim($tag);
			if ($tag!="") $tags[] = bbcode($tag);
		}

		$showlike    = ((x($alike,$item['uri'])) ? format_like($alike[$item['uri']],$alike[$item['uri'] . '-l'],'like',$item['uri']) : '');
		$showdislike = ((x($dlike,$item['uri']) && feature_enabled($conv->get_profile_owner(),'dislike'))  ? format_like($dlike[$item['uri']],$dlike[$item['uri'] . '-l'],'dislike',$item['uri']) : '');

		/*
		 * We should avoid doing this all the time, but it depends on the conversation mode
		 * And the conv mode may change when we change the conv, or it changes its mode
		 * Maybe we should establish a way to be notified about conversation changes
		 */
		$this->check_wall_to_wall();
		
		if($this->is_wall_to_wall() && ($this->get_owner_url() == $this->get_redirect_url()))
			$osparkle = ' sparkle';
		
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

		if($conv->is_writable()) {
			$like = array( t("I like this \x28toggle\x29"), t("like"));
			$dislike = array( t("I don't like this \x28toggle\x29"), t("dislike"));
			if ($shareable)
				$share = array( t('Share this'), t('share'));
		}

		if(strcmp(datetime_convert('UTC','UTC',$item['created']),datetime_convert('UTC','UTC','now - 12 hours')) > 0)
			$indent .= ' shiny';

		localize_item($item);

		$body = prepare_body($item,true);

		if($a->get_template_engine() === 'internal') {
			$body_e = template_escape($body);
			$name_e = template_escape($profile_name);
			$title_e = template_escape($item['title']);
			$location_e = template_escape($location);
			$owner_name_e = template_escape($this->get_owner_name());
		}
		else {
			$body_e = $body;
			$name_e = $profile_name;
			$title_e = $item['title'];
			$location_e = $location;
			$owner_name_e = $this->get_owner_name();
		}

		$tmp_item = array(
			'template' => $this->get_template(),
			
			'type' => implode("",array_slice(explode("/",$item['verb']),-1)),
			'tags' => $tags,
			'body' => $body_e,
			'text' => strip_tags($body_e),
			'id' => $this->get_id(),
			'linktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, ((strlen($item['author-link'])) ? $item['author-link'] : $item['url'])),
			'olinktitle' => sprintf( t('View %s\'s profile @ %s'), $this->get_owner_name(), ((strlen($item['owner-link'])) ? $item['owner-link'] : $item['url'])),
			'to' => t('to'),
			'wall' => t('Wall-to-Wall'),
			'vwall' => t('via Wall-To-Wall:'),
			'profile_url' => $profile_link,
			'item_photo_menu' => item_photo_menu($item),
			'name' => $name_e,
			'thumb' => $profile_avatar,
			'osparkle' => $osparkle,
			'sparkle' => $sparkle,
			'title' => $title_e,
			'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
			'ago' => (($item['app']) ? sprintf( t('%s from %s'),relative_date($item['created']),$item['app']) : relative_date($item['created'])),
			'lock' => $lock,
			'location' => $location_e,
			'indent' => $indent,
			'owner_url' => $this->get_owner_url(),
			'owner_photo' => $this->get_owner_photo(),
			'owner_name' => $owner_name_e,

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
		$conv = $this->get_conversation();

		return true;

		if($conv) {
			// This will allow us to comment on wall-to-wall items owned by our friends
			// and community forums even if somebody else wrote the post.
			return ($this->writable || ($this->is_visiting() && $conv->get_mode() == 'channel'));
		}
		return $this->writable;
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
		$template = get_markup_template($this->get_comment_box_template());
		$ww = '';
		if( ($conv->get_mode() === 'network') && $this->is_wall_to_wall() )
			$ww = 'ww';

		if($conv->is_writable() && $this->is_writable()) {
			$a = $this->get_app();
			$qc = $qcomment =  null;

			/*
			 * Hmmm, code depending on the presence of a particular plugin?
			 * This should be better if done by a hook
			 */
			if(in_array('qcomment',$a->plugins)) {
				$qc = ((local_user()) ? get_pconfig(local_user(),'qcomment','words') : null);
				$qcomment = (($qc) ? explode("\n",$qc) : null);
			}
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
				'$sourceapp' => t($a->sourcename),
				'$ww' => (($conv->get_mode() === 'network') ? $ww : '')
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
		$a = $this->get_app();
		$conv = $this->get_conversation();
		$this->wall_to_wall = false;
		
		if($this->is_toplevel()) {
			if( (! $this->get_data_value('self')) && ($conv->get_mode() !== 'channel')) {
				if($this->get_data_value('wall')) {

					// On the network page, I am the owner. On the display page it will be the profile owner.
					// This will have been stored in $a->page_contact by our calling page.
					// Put this person as the wall owner of the wall-to-wall notice.

					$this->owner_url = zid($a->page_contact['url']);
					$this->owner_photo = $a->page_contact['thumb'];
					$this->owner_name = $a->page_contact['name'];
					$this->wall_to_wall = true;
				}
				else if($this->get_data_value('owner-link')) {

					$owner_linkmatch = (($this->get_data_value('owner-link')) && link_compare($this->get_data_value('owner-link'),$this->get_data_value('author-link')));
					$alias_linkmatch = (($this->get_data_value('alias')) && link_compare($this->get_data_value('alias'),$this->get_data_value('author-link')));
					$owner_namematch = (($this->get_data_value('owner-name')) && $this->get_data_value('owner-name') == $this->get_data_value('author-name'));
					if((! $owner_linkmatch) && (! $alias_linkmatch) && (! $owner_namematch)) {

						// The author url doesn't match the owner (typically the contact)
						// and also doesn't match the contact alias. 
						// The name match is a hack to catch several weird cases where URLs are 
						// all over the park. It can be tricked, but this prevents you from
						// seeing "Bob Smith to Bob Smith via Wall-to-wall" and you know darn
						// well that it's the same Bob Smith. 

						// But it could be somebody else with the same name. It just isn't highly likely. 
						

						$this->owner_photo = $this->get_data_value('owner-avatar');
						$this->owner_name = $this->get_data_value('owner-name');
						$this->wall_to_wall = true;
						// If it is our contact, use a friendly redirect link
						if((link_compare($this->get_data_value('owner-link'),$this->get_data_value('url'))) 
							&& ($this->get_data_value('network') === NETWORK_DFRN)) {
							$this->owner_url = $this->get_redirect_url();
						}
						else
							$this->owner_url = zid($this->get_data_value('owner-link'));
					}
				}
			}
		}

		if(!$this->wall_to_wall) {
			$this->owner_url = '';
			$this->owner_photo = '';
			$this->owner_name = '';
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

