<?php /** @file */

if(class_exists('Conversation'))
	return;

require_once('boot.php');
require_once('include/BaseObject.php');
require_once('include/ItemObject.php');
require_once('include/text.php');

/**
 * A list of threads
 *
 */

class Conversation extends BaseObject {
	private $threads = array();
	private $mode = null;
	private $observer = null;
	private $writable = false;
	private $commentable = false;
	private $profile_owner = 0;
	private $preview = false;

	public function __construct($mode, $preview) {
		$this->set_mode($mode);
		$this->preview = $preview;
	}

	/**
	 * Set the mode we'll be displayed on
	 */
	private function set_mode($mode) {
		if($this->get_mode() == $mode)
			return;

		$a = $this->get_app();

		$this->observer = $a->get_observer();
		$ob_hash = (($this->observer) ? $this->observer['xchan_hash'] : '');

		switch($mode) {
			case 'network':
				$this->profile_owner = local_user();
				$this->writable = true;
				break;
			case 'channel':
				$this->profile_owner = $a->profile['profile_uid'];
				$this->writable = perm_is_allowed($this->profile_owner,$ob_hash,'post_comments');
				break;
			case 'display':
				// in this mode we set profile_owner after initialisation (from conversation()) and then 
				// pull some trickery which allows us to re-invoke this function afterward
				// it's an ugly hack so FIXME
//				$this->profile_owner = $a->profile['uid'];
				$this->writable = perm_is_allowed($this->profile_owner,$ob_hash,'post_comments');
				break;
			case 'page':
				$this->profile_owner = $a->profile['uid'];
				$this->writable = perm_is_allowed($this->profile_owner,$ob_hash,'post_comments');
				break;
			default:
				logger('[ERROR] Conversation::set_mode : Unhandled mode ('. $mode .').', LOGGER_DEBUG);
				return false;
				break;
		}
		$this->mode = $mode;
	}

	/**
	 * Get mode
	 */
	public function get_mode() {
		return $this->mode;
	}

	/**
	 * Check if page is writable
	 */
	public function is_writable() {
		return $this->writable;
	}

	public function is_commentable() {
		return $this->commentable;
	}

	/**
	 * Check if page is a preview
	 */
	public function is_preview() {
		return $this->preview;
	}

	/**
	 * Get profile owner
	 */
	public function get_profile_owner() {
		return $this->profile_owner;
	}

	public function set_profile_owner($uid) {
		$this->profile_owner = $uid;
		$mode = $this->get_mode();
		$this->mode = null;
		$this->set_mode($mode);
	}

	public function get_observer() {
		return $this->observer;
	}


	/**
	 * Add a thread to the conversation
	 *
	 * Returns:
	 *      _ The inserted item on success
	 *      _ false on failure
	 */
	public function add_thread($item) {
		$item_id = $item->get_id();
		if(!$item_id) {
			logger('[ERROR] Conversation::add_thread : Item has no ID!!', LOGGER_DEBUG);
			return false;
		}
		if($this->get_thread($item->get_id())) {
			logger('[WARN] Conversation::add_thread : Thread already exists ('. $item->get_id() .').', LOGGER_DEBUG);
			return false;
		}

		/*
		 * Only add things that will be displayed
		 */

		
		if(($item->get_data_value('id') != $item->get_data_value('parent')) && (activity_match($item->get_data_value('verb'),ACTIVITY_LIKE) || activity_match($item->get_data_value('verb'),ACTIVITY_DISLIKE))) {
			return false;
		}

		if(local_user() && $item->get_data_value('uid') == local_user())
			$this->commentable = true;

		if(($this->observer) && (! $this->writable)) {
			$this->commentable = can_comment_on_post($this->observer['xchan_hash'],$item['data']);
		}

		$item->set_conversation($this);
		$this->threads[] = $item;
		return end($this->threads);
	}

	/**
	 * Get data in a form usable by a conversation template
	 *
	 * We should find a way to avoid using those arguments (at least most of them)
	 *
	 * Returns:
	 *      _ The data requested on success
	 *      _ false on failure
	 */
	public function get_template_data($alike, $dlike) {
		$result = array();

		foreach($this->threads as $item) {

			$item_data = $item->get_template_data($alike, $dlike);
			if(!$item_data) {
				logger('[ERROR] Conversation::get_template_data : Failed to get item template data ('. $item->get_id() .').', LOGGER_DEBUG);
				return false;
			}
			$result[] = $item_data;
		}

		return $result;
	}

	/**
	 * Get a thread based on its item id
	 *
	 * Returns:
	 *      _ The found item on success
	 *      _ false on failure
	 */
	private function get_thread($id) {
		foreach($this->threads as $item) {
			if($item->get_id() == $id)
				return $item;
		}

		return false;
	}
}
