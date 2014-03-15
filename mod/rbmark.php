<?php /** @file */

require_once('acl_selectors.php');
require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/taxonomy.php');
require_once('include/conversation.php');
require_once('include/zot.php');
require_once('include/bookmarks.php');

/**
 * remote bookmark
 * 
 * https://yoursite/rbmark?f=&title=&url=&private=&remote_return=
 *
 * This can be called via either GET or POST, use POST for long body content as suhosin often limits GET parameter length
 *
 * f= placeholder, often required
 * title= link text
 * url= URL to bookmark
 * ischat=1 if this bookmark is a chatroom
 * private= Don't share this link
 * remote_return= absolute URL to return after posting is finished
 *
 */

function rbmark_post(&$a) {
	if($_POST['submit'] !== t('Save'))
		return;

	logger('rbmark_post: ' . print_r($_REQUEST,true));

	$channel = $a->get_channel();

	$t = array('url' => escape_tags($_REQUEST['url']),'term' => escape_tags($_REQUEST['title']));
	bookmark_add($channel,$channel,$t,((x($_REQUEST,'private')) ? intval($_REQUEST['private']) : 0),
		array('menu_id' => ((x($_REQUEST,'menu_id')) ? intval($_REQUEST['menu_id']) : 0),
			'menu_name' => ((x($_REQUEST,'menu_name')) ? escape_tags($_REQUEST['menu_name']) : ''),
			'ischat' => ((x($_REQUEST['ischat'])) ? intval($_REQUEST['ischat']) : 0)
		));

	goaway(z_root() . '/bookmarks');

}


function rbmark_content(&$a) {

	$o = '';

	if(! local_user()) {

		// The login procedure is going to bugger our $_REQUEST variables
		// so save them in the session.

		if(array_key_exists('url',$_REQUEST)) {
			$_SESSION['bookmark'] = $_REQUEST;
		}
		return login();
	}

	// If we have saved rbmark session variables, but nothing in the current $_REQUEST, recover the saved variables

	if((! array_key_exists('url',$_REQUEST)) && (array_key_exists('bookmark',$_SESSION))) {
		$_REQUEST = $_SESSION['bookmark'];
		unset($_SESSION['bookmark']);
	}

	if($_REQUEST['remote_return']) {
		$_SESSION['remote_return'] = $_REQUEST['remote_return'];
	}
	if(argc() > 1 && argv(1) === 'return') {
		if($_SESSION['remote_return'])
			goaway($_SESSION['remote_return']);
		goaway(z_root() . '/bookmarks');
	}

	$channel = $a->get_channel();

	$m = menu_list($channel,'',MENU_BOOKMARK);
	$menus = array();
	if($m) {
		$menus = array(0 => '');
		foreach($m as $n) {
			$menus[$n['menu_id']] = $n['menu_name'];
		}
	}
	$menu_select = array('menu_id',t('Select a bookmark folder'),false,'',$menus);


	$o .= replace_macros(get_markup_template('rbmark.tpl'), array(

		'$header' => t('Save Bookmark'),
		'$url' => array('url',t('URL of bookmark'),escape_tags($_REQUEST['url'])),
		'$title' => array('title',t('Description'),escape_tags($_REQUEST['title'])),
		'$ischat' => ((x($_REQUEST,'ischat')) ? intval($_REQUEST['ischat']) : 0),
		'$private' => ((x($_REQUEST,'private')) ? intval($_REQUEST['private']) : 0),
		'$submit' => t('Save'),
		'$menu_name' => array('menu_name',t('Or enter new bookmark folder name'),'',''),
		'$menus' => $menu_select

	));






	return $o;

}


