<?php /** @file */

require_once('acl_selectors.php');
require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/taxonomy.php');
require_once('include/conversation.php');
require_once('include/zot.php');

/**
 * remote post
 * 
 * https://yoursite/rpost?f=&title=&body=&remote_return=
 *
 * This can be called via either GET or POST, use POST for long body content as suhosin often limits GET parameter length
 *
 * f= placeholder, often required
 * title= Title of post
 * body= Body of post
 * source= Source application
 * remote_return= absolute URL to return after posting is finished
 * type= choices are 'html' or 'bbcode', default is 'bbcode'
 *
 */



function rpost_content(&$a) {

	$o = '';

	if(! local_user()) {
		if(remote_user()) {
			// redirect to your own site.
			// We can only do this with a GET request so you'll need to keep the text short or risk getting truncated
			// by the wretched beast called 'shusoin'. All the browsers now allow long GET requests, but suhosin
			// blocks them.

			$url = get_rpost_path($a->get_observer());
			// make sure we're not looping to our own hub
			if(($url) && (! stristr($url, $a->get_hostname()))) {
				foreach($_REQUEST as $key => $arg) {
					$url .= '&' . $key . '=' . $arg;
				}
				goaway($url);
			}
		}

		// The login procedure is going to bugger our $_REQUEST variables
		// so save them in the session.

		if(array_key_exists('body',$_REQUEST)) {
			$_SESSION['rpost'] = $_REQUEST;
		}
		return login();
	}

	// If we have saved rpost session variables, but nothing in the current $_REQUEST, recover the saved variables

	if((! array_key_exists('body',$_REQUEST)) && (array_key_exists('rpost',$_SESSION))) {
		$_REQUEST = $_SESSION['rpost'];
		unset($_SESSION['rpost']);
	}

	if($_REQUEST['remote_return']) {
		$_SESSION['remote_return'] = $_REQUEST['remote_return'];
	}
	if(argc() > 1 && argv(1) === 'return') {
		if($_SESSION['remote_return'])
			goaway($_SESSION['remote_return']);
		goaway(z_root() . '/network');
	}

	$plaintext = true;
	if(feature_enabled(local_user(),'richtext'))
		$plaintext = false;

	if(array_key_exists('type', $_REQUEST) && $_REQUEST['type'] === 'html') {
		require_once('include/html2bbcode.php');
		$_REQUEST['body'] = html2bbcode($_REQUEST['body']); 
	}

	$channel = $a->get_channel();

	$o .= replace_macros(get_markup_template('edpost_head.tpl'), array(
		'$title' => t('Edit post')
	));

	
//	$a->page['htmlhead'] .= replace_macros(get_markup_template('jot-header.tpl'), array(
//		'$baseurl' => $a->get_baseurl(),
//		'$editselect' =>  (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
//		'$ispublic' => '&nbsp;', // t('Visible to <strong>everybody</strong>'),
//		'$geotag' => $geotag,
//		'$nickname' => $channel['channel_address']
//	));



	$x = array(
		'is_owner' => true,
		'allow_location' => ((intval(get_pconfig($channel['channel_id'],'system','use_browser_location'))) ? '1' : ''),
		'default_location' => $channel['channel_location'],
		'nickname' => $channel['channel_address'],
		'lockstate' => (($channel['channel_allow_cid'] || $channel['channel_allow_gid'] 
			|| $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 'lock' : 'unlock'),
		'acl' => populate_acl($channel, $false),
		'bang' => '',
		'visitor' => 'block',
		'profile_uid' => local_user(),
		'title' => $_REQUEST['title'],
		'body' => $_REQUEST['body'],
		'source' => ((x($_REQUEST,'source')) ? strip_tags($_REQUEST['source']) : ''),
		'return_path' => 'rpost/return'
	);


	$o .= status_editor($a,$x);

	return $o;

}


