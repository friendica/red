<?php


function get_theme_config_file($theme){

	$base_theme = get_app()->theme_info['extends'];
	
	if (file_exists("view/theme/$theme/php/config.php")){
		return "view/theme/$theme/php/config.php";
	} 
	if (file_exists("view/theme/$base_theme/php/config.php")){
		return "view/theme/$base_theme/php/config.php";
	}
	return null;
}

function settings_aside(&$a) {


	// default is channel settings in the absence of other arguments

	if(argc() == 1) {
		// We are setting these values - don't use the argc(), argv() functions here
		$a->argc = 2;
		$a->argv[] = 'channel';
	}

	$abook_self_id = 0;

	// Retrieve the 'self' address book entry for use in the auto-permissions link
	if(local_user()) {		
		$abk = q("select abook_id from abook where abook_channel = %d and ( abook_flags & %d ) limit 1",
			intval(local_user()),
			intval(ABOOK_FLAG_SELF)
		);
		if($abk)
			$abook_self_id = $abk[0]['abook_id'];
	}


	$tabs = array(
		array(
			'label'	=> t('Account settings'),
			'url' 	=> $a->get_baseurl(true).'/settings/account',
			'selected'	=> ((argv(1) === 'account') ? 'active' : ''),
		),
	
		array(
			'label'	=> t('Channel settings'),
			'url' 	=> $a->get_baseurl(true).'/settings/channel',
			'selected'	=> ((argv(1) === 'channel') ? 'active' : ''),
		),
	
		array(
			'label'	=> t('Additional features'),
			'url' 	=> $a->get_baseurl(true).'/settings/features',
			'selected'	=> ((argv(1) === 'features') ? 'active' : ''),
		),

		array(
			'label'	=> t('Feature settings'),
			'url' 	=> $a->get_baseurl(true).'/settings/featured',
			'selected'	=> ((argv(1) === 'featured') ? 'active' : ''),
		),

		array(
			'label'	=> t('Display settings'),
			'url' 	=> $a->get_baseurl(true).'/settings/display',
			'selected'	=> ((argv(1) === 'display') ? 'active' : ''),
		),	
		
		array(
			'label' => t('Connected apps'),
			'url' => $a->get_baseurl(true) . '/settings/oauth',
			'selected' => ((argv(1) === 'oauth') ? 'active' : ''),
		),

		array(
			'label' => t('Export channel'),
			'url' => $a->get_baseurl(true) . '/uexport/basic',
			'selected' => ''
		),

//		array(
//			'label' => t('Export account'),
//			'url' => $a->get_baseurl(true) . '/uexport/complete',
//			'selected' => ''
//		),

		array(
			'label' => t('Automatic Permissions (Advanced)'),
			'url' => $a->get_baseurl(true) . '/connections/' . $abook_self_id,
			'selected' => ''
		),


	);
	
	$tabtpl = get_markup_template("generic_links_widget.tpl");
	$a->page['aside'] = replace_macros($tabtpl, array(
		'$title' => t('Settings'),
		'$class' => 'settings-widget',
		'$items' => $tabs,
	));

}


function settings_post(&$a) {

	if(! local_user())
		return;

	if(x($_SESSION,'submanage') && intval($_SESSION['submanage']))
		return;


//	if(count($a->user) && x($a->user,'uid') && $a->user['uid'] != local_user()) {
//		notice( t('Permission denied.') . EOL);
//		return;
//	}

	$old_page_flags = $a->user['page-flags'];

	if((argc() > 1) && (argv(1) === 'oauth') && x($_POST,'remove')){
		check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth');
		
		$key = $_POST['remove'];
		q("DELETE FROM tokens WHERE id='%s' AND uid=%d",
			dbesc($key),
			local_user());
		goaway($a->get_baseurl(true)."/settings/oauth/");
		return;			
	}

	if((argc() > 2) && (argv(1) === 'oauth')  && (argv(2) === 'edit'||(argv(2) === 'add')) && x($_POST,'submit')) {
		
		check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth');
		
		$name   	= ((x($_POST,'name')) ? $_POST['name'] : '');
		$key		= ((x($_POST,'key')) ? $_POST['key'] : '');
		$secret		= ((x($_POST,'secret')) ? $_POST['secret'] : '');
		$redirect	= ((x($_POST,'redirect')) ? $_POST['redirect'] : '');
		$icon		= ((x($_POST,'icon')) ? $_POST['icon'] : '');
		if ($name=="" || $key=="" || $secret==""){
			notice(t("Missing some important data!"));
			
		} else {
			if ($_POST['submit']==t("Update")){
				$r = q("UPDATE clients SET
							client_id='%s',
							pw='%s',
							name='%s',
							redirect_uri='%s',
							icon='%s',
							uid=%d
						WHERE client_id='%s'",
						dbesc($key),
						dbesc($secret),
						dbesc($name),
						dbesc($redirect),
						dbesc($icon),
						local_user(),
						dbesc($key));
			} else {
				$r = q("INSERT INTO clients
							(client_id, pw, name, redirect_uri, icon, uid)
						VALUES ('%s','%s','%s','%s','%s',%d)",
						dbesc($key),
						dbesc($secret),
						dbesc($name),
						dbesc($redirect),
						dbesc($icon),
						local_user());
			}
		}
		goaway($a->get_baseurl(true)."/settings/oauth/");
		return;
	}

	if((argc() > 1) && (argv(1) == 'featured')) {
		check_form_security_token_redirectOnErr('/settings/featured', 'settings_featured');

		call_hooks('feature_settings_post', $_POST);
		return;
	}



	if((argc() > 1) && (argv(1) === 'features')) {
		check_form_security_token_redirectOnErr('/settings/features', 'settings_features');
		foreach($_POST as $k => $v) {
			if(strpos($k,'feature_') === 0) {
				set_pconfig(local_user(),'feature',substr($k,8),((intval($v)) ? 1 : 0));
			}
		}
		return;
	}

	if((argc() > 1) && (argv(1) == 'display')) {
		
		check_form_security_token_redirectOnErr('/settings/display', 'settings_display');

		$theme = ((x($_POST,'theme')) ? notags(trim($_POST['theme']))  : $a->channel['channel_theme']);
		$mobile_theme = ((x($_POST,'mobile_theme')) ? notags(trim($_POST['mobile_theme']))  : '');
		$nosmile = ((x($_POST,'nosmile')) ? intval($_POST['nosmile'])  : 0);  
		$browser_update   = ((x($_POST,'browser_update')) ? intval($_POST['browser_update']) : 0);
		$browser_update   = $browser_update * 1000;
		if($browser_update < 10000)
			$browser_update = 10000;

		$itemspage   = ((x($_POST,'itemspage')) ? intval($_POST['itemspage']) : 20);
		if($itemspage > 100)
			$itemspage = 100;


		if($mobile_theme !== '') {
			set_pconfig(local_user(),'system','mobile_theme',$mobile_theme);
		}

		set_pconfig(local_user(),'system','update_interval', $browser_update);
		set_pconfig(local_user(),'system','itemspage', $itemspage);
		set_pconfig(local_user(),'system','no_smilies',$nosmile);


		if ($theme == $a->channel['channel_theme']){
			// call theme_post only if theme has not been changed
			if( ($themeconfigfile = get_theme_config_file($theme)) != null){
				require_once($themeconfigfile);
				theme_post($a);
			}
		}

		$r = q("UPDATE channel SET channel_theme = '%s' WHERE channel_id = %d LIMIT 1",
				dbesc($theme),
				intval(local_user())
		);
	
		call_hooks('display_settings_post', $_POST);
		goaway($a->get_baseurl(true) . '/settings/display' );
		return; // NOTREACHED
	}


	if(argc() > 1 && argv(1) === 'account') {

		check_form_security_token_redirectOnErr('/settings/account', 'settings_account');
	
		call_hooks('settings_account', $_POST);

		$errs = array();

		if((x($_POST,'npassword')) || (x($_POST,'confirm'))) {

			$newpass = $_POST['npassword'];
			$confirm = $_POST['confirm'];

			if($newpass != $confirm ) {
				$errs[] = t('Passwords do not match. Password unchanged.');
			}

			if((! x($newpass)) || (! x($confirm))) {
				$errs[] = t('Empty passwords are not allowed. Password unchanged.');
			}

			if(! $errs) {
				$salt = random_string(32);
				$password_encoded = hash('whirlpool', $salt . $newpass);
				$r = q("update account set account_salt = '%s', account_password = '%s' 
					where account_id = %d limit 1",
					dbesc($salt),
					dbesc($password_encoded),
					intval(get_account_id())
				);
				if($r)
					info( t('Password changed.') . EOL);
				else
					$errs[] = t('Password update failed. Please try again.');
			}
		}

		if($errs) {
			foreach($errs as $err)
				notice($err . EOL);
			$errs = array();
		}

		$email = ((x($_POST,'email')) ? trim(notags($_POST['email'])) : '');
		if($email != $account['account_email']) {
			$account = $a->get_account();
    	    if(! valid_email($email))
				$errs[] = t('Not valid email.');
			$adm = trim(get_config('system','admin_email'));
			if(($adm) && (strcasecmp($email,$adm) == 0)) {
				$errs[] = t('Protected email. Cannot change to that email.');
				$email = $a->user['email'];
			}
			if(! $errs) {
				$r = q("update account set account_email = '%s' where account_id = %d limit 1",
					dbesc($email),
					intval($account['account_id'])
				);
				if(! $r)
					$errs[] = t('System failure storing new email. Please try again.');
			}
		}

		if($errs) {
			foreach($errs as $err)
				notice($err . EOL);
		}
		goaway($a->get_baseurl(true) . '/settings/account' );
	}


	check_form_security_token_redirectOnErr('/settings', 'settings');
	
	call_hooks('settings_post', $_POST);

	
	$username         = ((x($_POST,'username'))   ? notags(trim($_POST['username']))     : '');
	$timezone         = ((x($_POST,'timezone'))   ? notags(trim($_POST['timezone']))     : '');
	$defloc           = ((x($_POST,'defloc'))     ? notags(trim($_POST['defloc']))       : '');
	$openid           = ((x($_POST,'openid_url')) ? notags(trim($_POST['openid_url']))   : '');
	$maxreq           = ((x($_POST,'maxreq'))     ? intval($_POST['maxreq'])             : 0);
	$expire           = ((x($_POST,'expire'))     ? intval($_POST['expire'])             : 0);
	$def_gid          = ((x($_POST,'group-selection')) ? intval($_POST['group-selection']) : 0);


	$expire_items     = ((x($_POST,'expire_items')) ? intval($_POST['expire_items'])	 : 0);
	$expire_notes     = ((x($_POST,'expire_notes')) ? intval($_POST['expire_notes'])	 : 0);
	$expire_starred   = ((x($_POST,'expire_starred')) ? intval($_POST['expire_starred']) : 0);
	$expire_photos    = ((x($_POST,'expire_photos'))? intval($_POST['expire_photos'])	 : 0);
	$expire_network_only    = ((x($_POST,'expire_network_only'))? intval($_POST['expire_network_only'])	 : 0);

	$allow_location   = (((x($_POST,'allow_location')) && (intval($_POST['allow_location']) == 1)) ? 1: 0);
	$publish          = (((x($_POST,'profile_in_directory')) && (intval($_POST['profile_in_directory']) == 1)) ? 1: 0);
	$page_flags       = (((x($_POST,'page-flags')) && (intval($_POST['page-flags']))) ? intval($_POST['page-flags']) : 0);
	$blockwall        = (((x($_POST,'blockwall')) && (intval($_POST['blockwall']) == 1)) ? 0: 1); // this setting is inverted!
	$blocktags        = (((x($_POST,'blocktags')) && (intval($_POST['blocktags']) == 1)) ? 0: 1); // this setting is inverted!
	$unkmail          = (((x($_POST,'unkmail')) && (intval($_POST['unkmail']) == 1)) ? 1: 0);
	$cntunkmail       = ((x($_POST,'cntunkmail')) ? intval($_POST['cntunkmail']) : 0);
	$suggestme        = ((x($_POST,'suggestme')) ? intval($_POST['suggestme'])  : 0);  
	$hide_friends     = (($_POST['hide_friends'] == 1) ? 1: 0);
	$hidewall         = (($_POST['hidewall'] == 1) ? 1: 0);
	$post_newfriend   = (($_POST['post_newfriend'] == 1) ? 1: 0);
	$post_joingroup   = (($_POST['post_joingroup'] == 1) ? 1: 0);
	$post_profilechange   = (($_POST['post_profilechange'] == 1) ? 1: 0);

	$arr = array();
	$arr['channel_r_stream']   = (($_POST['view_stream'])   ? $_POST['view_stream']   : 0);
	$arr['channel_r_profile']  = (($_POST['view_profile'])  ? $_POST['view_profile']  : 0);
	$arr['channel_r_photos']   = (($_POST['view_photos'])   ? $_POST['view_photos']   : 0);
	$arr['channel_r_abook']    = (($_POST['view_contacts']) ? $_POST['view_contacts'] : 0);
	$arr['channel_w_stream']   = (($_POST['send_stream'])   ? $_POST['send_stream']   : 0);
	$arr['channel_w_wall']     = (($_POST['post_wall'])     ? $_POST['post_wall']     : 0);
	$arr['channel_w_tagwall']  = (($_POST['tag_deliver'])   ? $_POST['tag_deliver']   : 0);
	$arr['channel_w_comment']  = (($_POST['post_comments']) ? $_POST['post_comments'] : 0);
	$arr['channel_w_mail']     = (($_POST['post_mail'])     ? $_POST['post_mail']     : 0);
	$arr['channel_w_photos']   = (($_POST['post_photos'])   ? $_POST['post_photos']   : 0);
	$arr['channel_w_chat']     = (($_POST['chat'])          ? $_POST['chat']          : 0);
	$arr['channel_a_delegate'] = (($_POST['delegate'])      ? $_POST['delegate']      : 0);
	$arr['channel_r_storage']  = (($_POST['view_storage'])  ? $_POST['view_storage']  : 0);
	$arr['channel_w_storage']  = (($_POST['write_storage']) ? $_POST['write_storage'] : 0);
	$arr['channel_r_pages']    = (($_POST['view_pages'])    ? $_POST['view_pages']    : 0);
	$arr['channel_w_pages']    = (($_POST['write_pages'])   ? $_POST['write_pages']    : 0);

	$defperms = 0;
	if(x($_POST['def_view_stream']))
		$defperms += $_POST['def_view_stream'];
	if(x($_POST['def_view_profile']))
		$defperms += $_POST['def_view_profile'];
	if(x($_POST['def_view_photos']))
		$defperms += $_POST['def_view_photos'];
	if(x($_POST['def_view_contacts']))
		$defperms += $_POST['def_view_contacts'];
	if(x($_POST['def_send_stream']))
		$defperms += $_POST['def_send_stream'];
	if(x($_POST['def_post_wall']))
		$defperms += $_POST['def_post_wall'];
	if(x($_POST['def_tag_deliver']))
		$defperms += $_POST['def_tag_deliver'];
	if(x($_POST['def_post_comments']))
		$defperms += $_POST['def_post_comments'];
	if(x($_POST['def_post_mail']))
		$defperms += $_POST['def_post_mail'];
	if(x($_POST['def_post_photos']))
		$defperms += $_POST['def_post_photos'];
	if(x($_POST['def_chat']))
		$defperms += $_POST['def_chat'];
	if(x($_POST['def_delegate']))
		$defperms += $_POST['def_delegate'];
	if(x($_POST['def_view_storage']))
		$defperms += $_POST['def_view_storage'];
	if(x($_POST['def_write_storage']))
		$defperms += $_POST['def_write_storage'];
	if(x($_POST['def_view_pages']))
		$defperms += $_POST['def_view_pages'];
	if(x($_POST['def_write_pages']))
		$defperms += $_POST['def_write_pages'];

	$notify = 0;

	if(x($_POST,'notify1'))
		$notify += intval($_POST['notify1']);
	if(x($_POST,'notify2'))
		$notify += intval($_POST['notify2']);
	if(x($_POST,'notify3'))
		$notify += intval($_POST['notify3']);
	if(x($_POST,'notify4'))
		$notify += intval($_POST['notify4']);
	if(x($_POST,'notify5'))
		$notify += intval($_POST['notify5']);
	if(x($_POST,'notify6'))
		$notify += intval($_POST['notify6']);
	if(x($_POST,'notify7'))
		$notify += intval($_POST['notify7']);
	if(x($_POST,'notify8'))
		$notify += intval($_POST['notify8']);


	$channel = $a->get_channel();

	$err = '';

	$name_change = false;

	if($username != $channel['channel_name']) {
		$name_change = true;
		require_once('include/identity.php');
		$err = validate_channelname($username);
		if($err) {
			notice($err);
			return;
		}
	}


	if($timezone != $channel['channel_timezone']) {
		if(strlen($timezone))
			date_default_timezone_set($timezone);
	}

	$str_group_allow   = perms2str($_POST['group_allow']);
	$str_contact_allow = perms2str($_POST['contact_allow']);
	$str_group_deny    = perms2str($_POST['group_deny']);
	$str_contact_deny  = perms2str($_POST['contact_deny']);

	set_pconfig(local_user(),'expire','items', $expire_items);
	set_pconfig(local_user(),'expire','notes', $expire_notes);
	set_pconfig(local_user(),'expire','starred', $expire_starred);
	set_pconfig(local_user(),'expire','photos', $expire_photos);
	set_pconfig(local_user(),'expire','network_only', $expire_network_only);
	set_pconfig(local_user(),'system','use_browser_location',$allow_location);
	set_pconfig(local_user(),'system','suggestme', $suggestme);
	set_pconfig(local_user(),'system','post_newfriend', $post_newfriend);
	set_pconfig(local_user(),'system','post_joingroup', $post_joingroup);
	set_pconfig(local_user(),'system','post_profilechange', $post_profilechange);

/*
	if($page_flags == PAGE_PRVGROUP) {
		$hidewall = 1;
		if((! $str_contact_allow) && (! $str_group_allow) && (! $str_contact_deny) && (! $str_group_deny)) {
			if($def_gid) {
				info( t('Private forum has no privacy permissions. Using default privacy group.'). EOL);
				$str_group_allow = '<' . $def_gid . '>';
			}
			else {
				notice( t('Private forum has no privacy permissions and no default privacy group.') . EOL);
			} 
		}
	}

*/

/*
	$r = q("UPDATE `user` SET `username` = '%s', `email` = '%s', `openid` = '%s', `timezone` = '%s',  `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s', `notify-flags` = %d, `page-flags` = %d, `default-location` = '%s', `allow_location` = %d, `maxreq` = %d, `expire` = %d, `openidserver` = '%s', `def_gid` = %d, `blockwall` = %d, `hidewall` = %d, `blocktags` = %d, `unkmail` = %d, `cntunkmail` = %d  WHERE `uid` = %d LIMIT 1",
			dbesc($username),
			dbesc($email),
			dbesc($openid),
			dbesc($timezone),
			dbesc($str_contact_allow),
			dbesc($str_group_allow),
			dbesc($str_contact_deny),
			dbesc($str_group_deny),
			intval($notify),
			intval($page_flags),
			dbesc($defloc),
			intval($allow_location),
			intval($maxreq),
			intval($expire),
			dbesc($openidserver),
			intval($def_gid),
			intval($blockwall),
			intval($hidewall),
			intval($blocktags),
			intval($unkmail),
			intval($cntunkmail),
			intval(local_user())
	);
*/

	$r = q("update channel set channel_name = '%s', channel_timezone = '%s', channel_notifyflags = %d, channel_max_anon_mail = %d, channel_max_friend_req = %d, channel_expire_days = %d, channel_r_stream = %d, channel_r_profile = %d, channel_r_photos = %d, channel_r_abook = %d, channel_w_stream = %d, channel_w_wall = %d, channel_w_tagwall = %d, channel_w_comment = %d, channel_w_mail = %d, channel_w_photos = %d, channel_w_chat = %d, channel_a_delegate = %d, channel_r_storage = %d, channel_w_storage = %d, channel_r_pages = %d, channel_w_pages = %d where channel_id = %d limit 1",
		dbesc($username),
		dbesc($timezone),
		intval($notify),
		intval($unkmail),
		intval($maxreq),
		intval($expire),
		intval($arr['channel_r_stream']),
		intval($arr['channel_r_profile']),
		intval($arr['channel_r_photos']),
		intval($arr['channel_r_abook']), 
		intval($arr['channel_w_stream']),
		intval($arr['channel_w_wall']),  
		intval($arr['channel_w_tagwall']),
		intval($arr['channel_w_comment']),
		intval($arr['channel_w_mail']),   
		intval($arr['channel_w_photos']), 
		intval($arr['channel_w_chat']),
		intval($arr['channel_a_delegate']),
		intval($arr['channel_r_storage']),
		intval($arr['channel_w_storage']),
		intval($arr['channel_r_pages']),
		intval($arr['channel_w_pages']),
		intval(local_user())
	);   

	if($r)
		info( t('Settings updated.') . EOL);

	$r = q("UPDATE `profile` 
		SET `publish` = %d, 
		`hide_friends` = %d
		WHERE `is_default` = 1 AND `uid` = %d LIMIT 1",
		intval($publish),
		intval($hide_friends),
		intval(local_user())
	);

	if($name_change) {
		$r = q("update xchan set xchan_name = '%s', xchan_name_date = '%s' where xchan_hash = '%s' limit 1",
			dbesc($username),
			dbesc(datetime_convert()),
			dbesc($channel['channel_hash'])
		);
		$r = q("update profile set name = '%s' where uid = %d and is_default = 1",
			dbesc($username),
			intval($channel['channel_id'])
		);
		// we really need to send out notifications to all our friends
	}

	proc_run('php','include/directory.php',local_user());


	//$_SESSION['theme'] = $theme;
	if($email_changed && $a->config['system']['register_policy'] == REGISTER_VERIFY) {

		// FIXME - set to un-verified, blocked and redirect to logout
		// Why? Are we verifying people or email addresses?

	}

	goaway($a->get_baseurl(true) . '/settings' );
	return; // NOTREACHED
}
		

if(! function_exists('settings_content')) {
function settings_content(&$a) {

	$o = '';
	nav_set_selected('settings');


	if(! local_user()) {
		notice( t('Permission denied.') . EOL );
		return;
	}

//	if(x($_SESSION,'submanage') && intval($_SESSION['submanage'])) {
//		notice( t('Permission denied.') . EOL );
//		return;
//	}
	

		
	if((argc() > 1) && (argv(1) === 'oauth')) {
		
		if((argc() > 2) && (argv(2) === 'add')) {
			$tpl = get_markup_template("settings_oauth_edit.tpl");
			$o .= replace_macros($tpl, array(
				'$form_security_token' => get_form_security_token("settings_oauth"),
				'$title'	=> t('Add application'),
				'$submit'	=> t('Submit'),
				'$cancel'	=> t('Cancel'),
				'$name'		=> array('name', t('Name'), '', ''),
				'$key'		=> array('key', t('Consumer Key'), '', ''),
				'$secret'	=> array('secret', t('Consumer Secret'), '', ''),
				'$redirect'	=> array('redirect', t('Redirect'), '', ''),
				'$icon'		=> array('icon', t('Icon url'), '', ''),
			));
			return $o;
		}
		
		if((argc() > 3) && (argv(2) === 'edit')) {
			$r = q("SELECT * FROM clients WHERE client_id='%s' AND uid=%d",
					dbesc(argv(3)),
					local_user());
			
			if (!count($r)){
				notice(t("You can't edit this application."));
				return;
			}
			$app = $r[0];
			
			$tpl = get_markup_template("settings_oauth_edit.tpl");
			$o .= replace_macros($tpl, array(
				'$form_security_token' => get_form_security_token("settings_oauth"),
				'$title'	=> t('Add application'),
				'$submit'	=> t('Update'),
				'$cancel'	=> t('Cancel'),
				'$name'		=> array('name', t('Name'), $app['name'] , ''),
				'$key'		=> array('key', t('Consumer Key'), $app['client_id'], ''),
				'$secret'	=> array('secret', t('Consumer Secret'), $app['pw'], ''),
				'$redirect'	=> array('redirect', t('Redirect'), $app['redirect_uri'], ''),
				'$icon'		=> array('icon', t('Icon url'), $app['icon'], ''),
			));
			return $o;
		}
		
		if((argc() > 3) && (argv(2) === 'delete')) {
			check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth', 't');
		
			$r = q("DELETE FROM clients WHERE client_id='%s' AND uid=%d",
					dbesc(argv(3)),
					local_user());
			goaway($a->get_baseurl(true)."/settings/oauth/");
			return;			
		}
		
		
		$r = q("SELECT clients.*, tokens.id as oauth_token, (clients.uid=%d) AS my 
				FROM clients
				LEFT JOIN tokens ON clients.client_id=tokens.client_id
				WHERE clients.uid IN (%d,0)",
				local_user(),
				local_user());
		
		
		$tpl = get_markup_template("settings_oauth.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_oauth"),
			'$baseurl'	=> $a->get_baseurl(true),
			'$title'	=> t('Connected Apps'),
			'$add'		=> t('Add application'),
			'$edit'		=> t('Edit'),
			'$delete'		=> t('Delete'),
			'$consumerkey' => t('Client key starts with'),
			'$noname'	=> t('No name'),
			'$remove'	=> t('Remove authorization'),
			'$apps'		=> $r,
		));
		return $o;
		
	}
	if((argc() > 1) && (argv(1) === 'featured')) {
		$settings_addons = "";
		
		$r = q("SELECT * FROM `hook` WHERE `hook` = 'plugin_settings' ");
		if(! count($r))
			$settings_addons = t('No feature settings configured');

		call_hooks('feature_settings', $settings_addons);
		
		
		$tpl = get_markup_template("settings_addons.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_featured"),
			'$title'	=> t('Feature Settings'),
			'$settings_addons' => $settings_addons
		));
		return $o;
	}


	/*
	 * ACCOUNT SETTINGS
	 */


	if((argc() > 1) && (argv(1) === 'account')) {
		$account_settings = "";
		
		call_hooks('account_settings', $account_settings);

		$email      = $a->account['account_email'];
		
		
		$tpl = get_markup_template("settings_account.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_account"),
			'$title'	=> t('Account Settings'),
			'$h_pass' 	=> t('Password Settings'),
			'$password1'=> array('npassword', t('New Password:'), '', ''),
			'$password2'=> array('confirm', t('Confirm:'), '', t('Leave password fields blank unless changing')),
			'$submit' 	=> t('Submit'),
			'$email' 	=> array('email', t('Email Address:'), $email, ''),
			'$removeme' => t('Remove Account'),
			'$permanent' => t('Warning: This action is permanent and cannot be reversed.'),
			'$account_settings' => $account_settings
		));
		return $o;
	}



	if((argc() > 1) && (argv(1) === 'features')) {
		
		$arr = array();
		$features = get_features();

		foreach($features as $fname => $fdata) {
			$arr[$fname] = array();
			$arr[$fname][0] = $fdata[0];
			foreach(array_slice($fdata,1) as $f) {
				$arr[$fname][1][] = array('feature_' .$f[0],$f[1],((intval(get_pconfig(local_user(),'feature',$f[0]))) ? "1" : ''),$f[2],array(t('Off'),t('On')));
			}
		}
		
		$tpl = get_markup_template("settings_features.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_features"),
			'$title'	=> t('Additional Features'),
			'$features' => $arr,
			'$submit'   => t('Submit'),
			'$field_yesno'  => 'field_yesno.tpl',
		));
		return $o;
	}





	if((argc() > 1) && (argv(1) === 'connectors')) {

		$settings_connectors = "";
		
		call_hooks('connector_settings', $settings_connectors);

		$r = null;

		$tpl = get_markup_template("settings_connectors.tpl");

		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_connectors"),
			'$title'	=> t('Connector Settings'),
			'$submit' => t('Submit'),
			'$settings_connectors' => $settings_connectors
		));

		call_hooks('display_settings', $o);
		return $o;
	}

	/*
	 * DISPLAY SETTINGS
	 */
	if((argc() > 1) && (argv(1) === 'display')) {
		$default_theme = get_config('system','theme');
		if(! $default_theme)
			$default_theme = 'default';
		$default_mobile_theme = get_config('system','mobile-theme');
		if(! $mobile_default_theme)
			$mobile_default_theme = 'none';

		$allowed_themes_str = get_config('system','allowed_themes');
		$allowed_themes_raw = explode(',',$allowed_themes_str);
		$allowed_themes = array();
		if(count($allowed_themes_raw))
			foreach($allowed_themes_raw as $x) 
				if(strlen(trim($x)) && is_dir("view/theme/$x"))
					$allowed_themes[] = trim($x);

		
		$themes = array();
		$mobile_themes = array("---" => t('No special theme for mobile devices'));
		$files = glob('view/theme/*');
		if($allowed_themes) {
			foreach($allowed_themes as $th) {
				$f = $th;
				$is_experimental = file_exists('view/theme/' . $th . '/experimental');
				$unsupported = file_exists('view/theme/' . $th . '/unsupported');
				$is_mobile = file_exists('view/theme/' . $th . '/mobile');
				if (!$is_experimental or ($is_experimental && (get_config('experimentals','exp_themes')==1 or get_config('experimentals','exp_themes')===false))){ 
					$theme_name = (($is_experimental) ?  sprintf("%s - \x28Experimental\x29", $f) : $f);
					if($is_mobile) {
						$mobile_themes[$f]=$theme_name;
					}
					else {
						$themes[$f]=$theme_name;
					}
				}
			}
		}
		$theme_selected = (!x($_SESSION,'theme')? $default_theme : $_SESSION['theme']);
		$mobile_theme_selected = (!x($_SESSION,'mobile-theme')? $default_mobile_theme : $_SESSION['mobile-theme']);
		
		$browser_update = intval(get_pconfig(local_user(), 'system','update_interval'));
		$browser_update = (($browser_update == 0) ? 40 : $browser_update / 1000); // default if not set: 40 seconds

		$itemspage = intval(get_pconfig(local_user(), 'system','itemspage'));
		$itemspage = (($itemspage > 0 && $itemspage < 101) ? $itemspage : 20); // default if not set: 20 items
		
		$nosmile = get_pconfig(local_user(),'system','no_smilies');
		$nosmile = (($nosmile===false)? '0': $nosmile); // default if not set: 0


		$theme_config = "";
		if( ($themeconfigfile = get_theme_config_file($theme_selected)) != null){
			require_once($themeconfigfile);
			$theme_config = theme_content($a);
		}
		
		$tpl = get_markup_template("settings_display.tpl");
		$o = replace_macros($tpl, array(
			'$ptitle' 	=> t('Display Settings'),
			'$form_security_token' => get_form_security_token("settings_display"),
			'$submit' 	=> t('Submit'),
			'$baseurl' => $a->get_baseurl(true),
			'$uid' => local_user(),
		
			'$theme'	=> array('theme', t('Display Theme:'), $theme_selected, '', $themes, 'preview'),
			'$mobile_theme'	=> array('mobile_theme', t('Mobile Theme:'), $mobile_theme_selected, '', $mobile_themes, ''),
			'$ajaxint'   => array('browser_update',  t("Update browser every xx seconds"), $browser_update, t('Minimum of 10 seconds, no maximum')),
			'$itemspage'   => array('itemspage',  t("Maximum number of conversations to load at any time:"), $itemspage, t('Maximum of 100 items')),
			'$nosmile'	=> array('nosmile', t("Don't show emoticons"), $nosmile, ''),
			
			'$theme_config' => $theme_config,
		));
		
		return $o;
	}
	
	



	if(argv(1) === 'channel') {

		require_once('include/acl_selectors.php');
		require_once('include/permissions.php');




		$p = q("SELECT * FROM `profile` WHERE `is_default` = 1 AND `uid` = %d LIMIT 1",
			intval(local_user())
		);
		if(count($p))
			$profile = $p[0];

		load_pconfig(local_user(),'expire');

		$channel = $a->get_channel();


		$global_perms = get_perms();

		$permiss = array();

		$perm_opts = array(
			array( t('Nobody except yourself'), 0),
			array( t('Only those you specifically allow'), PERMS_SPECIFIC), 
			array( t('Anybody in your address book'), PERMS_CONTACTS),
			array( t('Anybody on this website'), PERMS_SITE),
			array( t('Anybody in this network'), PERMS_NETWORK),
			array( t('Anybody on the internet'), PERMS_PUBLIC)
		);


		foreach($global_perms as $k => $perm) {
			$options = array();
			foreach($perm_opts as $opt) {
				if((! $perm[2]) && $opt[1] == PERMS_PUBLIC)
					continue;
				$options[$opt[1]] = $opt[0];
			}
			$permiss[] = array($k,$perm[3],$channel[$perm[0]],$perm[4],$options);			
		}


//		logger('permiss: ' . print_r($permiss,true));



		$username   = $channel['channel_name'];
		$nickname   = $channel['channel_address'];
		$timezone   = $channel['channel_timezone'];
		$notify     = $channel['channel_notifyflags'];
		$defloc     = $channel['channel_location'];

		$maxreq     = $channel['channel_max_friend_req'];
		$expire     = $channel['channel_expire_days'];

		$blockwall  = $a->user['blockwall'];
		$blocktags  = $a->user['blocktags'];
		$unkmail    = $a->user['unkmail'];
		$cntunkmail = $a->user['cntunkmail'];

		$expire_items = get_pconfig(local_user(), 'expire','items');
		$expire_items = (($expire_items===false)? '1' : $expire_items); // default if not set: 1
	
		$expire_notes = get_pconfig(local_user(), 'expire','notes');
		$expire_notes = (($expire_notes===false)? '1' : $expire_notes); // default if not set: 1

		$expire_starred = get_pconfig(local_user(), 'expire','starred');
		$expire_starred = (($expire_starred===false)? '1' : $expire_starred); // default if not set: 1
	
		$expire_photos = get_pconfig(local_user(), 'expire','photos');
		$expire_photos = (($expire_photos===false)? '0' : $expire_photos); // default if not set: 0

		$expire_network_only = get_pconfig(local_user(), 'expire','network_only');
		$expire_network_only = (($expire_network_only===false)? '0' : $expire_network_only); // default if not set: 0


		$suggestme = get_pconfig(local_user(), 'system','suggestme');
		$suggestme = (($suggestme===false)? '0': $suggestme); // default if not set: 0

		$post_newfriend = get_pconfig(local_user(), 'system','post_newfriend');
		$post_newfriend = (($post_newfriend===false)? '0': $post_newfriend); // default if not set: 0

		$post_joingroup = get_pconfig(local_user(), 'system','post_joingroup');
		$post_joingroup = (($post_joingroup===false)? '0': $post_joingroup); // default if not set: 0

		$post_profilechange = get_pconfig(local_user(), 'system','post_profilechange');
		$post_profilechange = (($post_profilechange===false)? '0': $post_profilechange); // default if not set: 0

	
		$timezone = date_default_timezone_get();



		$opt_tpl = get_markup_template("field_yesno.tpl");
		if(get_config('system','publish_all')) {
			$profile_in_dir = '<input type="hidden" name="profile_in_directory" value="1" />';
		}
		else {
			$profile_in_dir = replace_macros($opt_tpl,array(
				'$field' 	=> array('profile_in_directory', t('Publish your default profile in the network directory'), $profile['publish'], '', array(t('No'),t('Yes'))),
			));
		}

		$suggestme = replace_macros($opt_tpl,array(
				'$field' 	=> array('suggestme',  t('Allow us to suggest you as a potential friend to new members?'), $suggestme, '', array(t('No'),t('Yes'))),

		));

		$subdir = ((strlen($a->get_path())) ? '<br />' . t('or') . ' ' . $a->get_baseurl(true) . '/channel/' . $nickname : '');

		$tpl_addr = get_markup_template("settings_nick_set.tpl");

		$prof_addr = replace_macros($tpl_addr,array(
			'$desc' => t('Your channel address is'),
			'$nickname' => $nickname,
			'$subdir' => $subdir,
			'$basepath' => $a->get_hostname()
		));

		$stpl = get_markup_template('settings.tpl');

		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		$expire_arr = array(
			'days' => array('expire',  t("Automatically expire posts after this many days:"), $expire, t('If empty, posts will not expire. Expired posts will be deleted')),
			'advanced' => t('Advanced expiration settings'),
			'label' => t('Advanced Expiration'),
			'items' => array('expire_items',  t("Expire posts:"), $expire_items, '', array(t('No'),t('Yes'))),

			'starred' => array('expire_starred',  t("Expire starred posts:"), $expire_starred, '', array(t('No'),t('Yes'))),
			'photos' => array('expire_photos',  t("Expire photos:"), $expire_photos, '', array(t('No'),t('Yes'))),		
			'network_only' => array('expire_network_only',  t("Only expire posts by others:"), $expire_network_only, '', array(t('No'),t('Yes'))),		
		);





		require_once('include/group.php');
		$group_select = mini_group_select(local_user(),$a->user['def_gid']);

		$o .= replace_macros($stpl,array(
			'$ptitle' 	=> t('Channel Settings'),

			'$submit' 	=> t('Submit'),
			'$baseurl' => $a->get_baseurl(true),
			'$uid' => local_user(),
			'$form_security_token' => get_form_security_token("settings"),
			'$nickname_block' => $prof_addr,
		
		
			'$h_basic' 	=> t('Basic Settings'),
			'$username' => array('username',  t('Full Name:'), $username,''),
			'$email' 	=> array('email', t('Email Address:'), $email, ''),
			'$timezone' => array('timezone_select' , t('Your Timezone:'), select_timezone($timezone), ''),
			'$defloc'	=> array('defloc', t('Default Post Location:'), $defloc, ''),
			'$allowloc' => array('allow_location', t('Use Browser Location:'), (intval(get_pconfig(local_user(),'system','use_browser_location')) == 1), ''),
		

			'$h_prv' 	=> t('Security and Privacy Settings'),

			'$permiss_arr' => $permiss,

			'$maxreq' 	=> array('maxreq', t('Maximum Friend Requests/Day:'), intval($channel['channel_max_friend_req']) , t('May reduce spam activity')),
			'$permissions' => t('Default Post Permissions'),
			'$permdesc' => t("\x28click to open/close\x29"),
			'$aclselect' => populate_acl($a->user,$celeb),
			'$suggestme' => $suggestme,

			'$group_select' => $group_select,


			'$expire'	=> $expire_arr,

			'$profile_in_dir' => $profile_in_dir,
			'$hide_friends' => $hide_friends,
			'$hide_wall' => $hide_wall,
			'$unkmail' => $unkmail,		
			'$cntunkmail' 	=> array('cntunkmail', t('Maximum private messages per day from unknown people:'), intval($channel['channel_max_anon_mail']) ,t("Useful to reduce spamming")),
		
		
			'$h_not' 	=> t('Notification Settings'),
			'$activity_options' => t('By default post a status message when:'),
			'$post_newfriend' => array('post_newfriend',  t('accepting a friend request'), $post_newfriend, ''),
			'$post_joingroup' => array('post_joingroup',  t('joining a forum/community'), $post_joingroup, ''),
			'$post_profilechange' => array('post_profilechange',  t('making an <em>interesting</em> profile change'), $post_profilechange, ''),
			'$lbl_not' 	=> t('Send a notification email when:'),
			'$notify1'	=> array('notify1', t('You receive an introduction'), ($notify & NOTIFY_INTRO), NOTIFY_INTRO, ''),
			'$notify2'	=> array('notify2', t('Your introductions are confirmed'), ($notify & NOTIFY_CONFIRM), NOTIFY_CONFIRM, ''),
			'$notify3'	=> array('notify3', t('Someone writes on your profile wall'), ($notify & NOTIFY_WALL), NOTIFY_WALL, ''),
			'$notify4'	=> array('notify4', t('Someone writes a followup comment'), ($notify & NOTIFY_COMMENT), NOTIFY_COMMENT, ''),
			'$notify5'	=> array('notify5', t('You receive a private message'), ($notify & NOTIFY_MAIL), NOTIFY_MAIL, ''),
			'$notify6'  => array('notify6', t('You receive a friend suggestion'), ($notify & NOTIFY_SUGGEST), NOTIFY_SUGGEST, ''),		
			'$notify7'  => array('notify7', t('You are tagged in a post'), ($notify & NOTIFY_TAGSELF), NOTIFY_TAGSELF, ''),		
			'$notify8'  => array('notify8', t('You are poked/prodded/etc. in a post'), ($notify & NOTIFY_POKE), NOTIFY_POKE, ''),		
		
		
			'$h_advn' => t('Advanced Account/Page Type Settings'),
			'$h_descadvn' => t('Change the behaviour of this account for special situations'),
			'$pagetype' => $pagetype,
		
		));

		call_hooks('settings_form',$o);

		$o .= '</form>' . "\r\n";

		return $o;
	}
}}

