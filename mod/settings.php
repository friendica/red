<?php /** @file */

require_once('include/zot.php');

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

function settings_init(&$a) {
	if(! local_channel())
		return;

	if($_SESSION['delegate'])
		return;

	$a->profile_uid = local_channel();

	// default is channel settings in the absence of other arguments

	if(argc() == 1) {
		// We are setting these values - don't use the argc(), argv() functions here
		$a->argc = 2;
		$a->argv[] = 'channel';
	}



}


function settings_post(&$a) {

	if(! local_channel())
		return;

	if($_SESSION['delegate'])
		return;

	$channel = $a->get_channel();

	 logger('mod_settings: ' . print_r($_REQUEST,true));


	if((argc() > 1) && (argv(1) === 'oauth') && x($_POST,'remove')){
		check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth');
		
		$key = $_POST['remove'];
		q("DELETE FROM tokens WHERE id='%s' AND uid=%d",
			dbesc($key),
			local_channel());
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
		$ok = true;
		if($name == '') {
			$ok = false;
			notice( t('Name is required') . EOL);
		}
		if($key == '' || $secret == '') {
			$ok = false;
			notice( t('Key and Secret are required') . EOL);
		}
	
		if($ok) {
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
						local_channel(),
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
						local_channel());
			}
		}
		goaway($a->get_baseurl(true)."/settings/oauth/");
		return;
	}

	if((argc() > 1) && (argv(1) == 'featured')) {
		check_form_security_token_redirectOnErr('/settings/featured', 'settings_featured');

		call_hooks('feature_settings_post', $_POST);

		if($_POST['dspr-submit']) {
			set_pconfig(local_channel(),'system','diaspora_public_comments',intval($_POST['dspr_pubcomment']));
			set_pconfig(local_channel(),'system','prevent_tag_hijacking',intval($_POST['dspr_hijack']));
			info( t('Diaspora Policy Settings updated.') . EOL);
		}

		build_sync_packet();
		return;
	}



	if((argc() > 1) && (argv(1) === 'features')) {
		check_form_security_token_redirectOnErr('/settings/features', 'settings_features');

		// Build list of features and check which are set
		$features = get_features();
		$all_features = array();
		foreach($features as $k => $v) {
			foreach($v as $f) 
				$all_features[] = $f[0];
		}
		foreach($all_features as $k) {
			if(x($_POST,"feature_$k"))
				set_pconfig(local_channel(),'feature',$k, 1);
			else
				set_pconfig(local_channel(),'feature',$k, 0);
		}
		build_sync_packet();
		return;
	}

	if((argc() > 1) && (argv(1) == 'display')) {
		
		check_form_security_token_redirectOnErr('/settings/display', 'settings_display');

		$theme = ((x($_POST,'theme')) ? notags(trim($_POST['theme']))  : $a->channel['channel_theme']);
		$mobile_theme = ((x($_POST,'mobile_theme')) ? notags(trim($_POST['mobile_theme']))  : '');
		$user_scalable = ((x($_POST,'user_scalable')) ? intval($_POST['user_scalable'])  : 0);
		$nosmile = ((x($_POST,'nosmile')) ? intval($_POST['nosmile'])  : 0); 
		$title_tosource = ((x($_POST,'title_tosource')) ? intval($_POST['title_tosource'])  : 0);		 
		$channel_list_mode = ((x($_POST,'channel_list_mode')) ? intval($_POST['channel_list_mode']) : 0);
		$network_list_mode = ((x($_POST,'network_list_mode')) ? intval($_POST['network_list_mode']) : 0);

		$channel_divmore_height = ((x($_POST,'channel_divmore_height')) ? intval($_POST['channel_divmore_height']) : 400);
		if($channel_divmore_height < 50)
			$channel_divmore_height = 50;
		$network_divmore_height = ((x($_POST,'network_divmore_height')) ? intval($_POST['network_divmore_height']) : 400);
		if($network_divmore_height < 50)
			$network_divmore_height = 50;

		$browser_update   = ((x($_POST,'browser_update')) ? intval($_POST['browser_update']) : 0);
		$browser_update   = $browser_update * 1000;
		if($browser_update < 10000)
			$browser_update = 10000;

		$itemspage   = ((x($_POST,'itemspage')) ? intval($_POST['itemspage']) : 20);
		if($itemspage > 100)
			$itemspage = 100;


		if ($mobile_theme == "---") 
			del_pconfig(local_channel(),'system','mobile_theme');
		else {
			set_pconfig(local_channel(),'system','mobile_theme',$mobile_theme);
		}

		set_pconfig(local_channel(),'system','user_scalable',$user_scalable);
		set_pconfig(local_channel(),'system','update_interval', $browser_update);
		set_pconfig(local_channel(),'system','itemspage', $itemspage);
		set_pconfig(local_channel(),'system','no_smilies',1-intval($nosmile));
		set_pconfig(local_channel(),'system','title_tosource',$title_tosource);
		set_pconfig(local_channel(),'system','channel_list_mode', $channel_list_mode);
		set_pconfig(local_channel(),'system','network_list_mode', $network_list_mode);
		set_pconfig(local_channel(),'system','channel_divmore_height', $channel_divmore_height);
		set_pconfig(local_channel(),'system','network_divmore_height', $network_divmore_height);

		if ($theme == $a->channel['channel_theme']){
			// call theme_post only if theme has not been changed
			if( ($themeconfigfile = get_theme_config_file($theme)) != null){
				require_once($themeconfigfile);
				theme_post($a);
			}
		}

		$r = q("UPDATE channel SET channel_theme = '%s' WHERE channel_id = %d",
				dbesc($theme),
				intval(local_channel())
		);
	
		call_hooks('display_settings_post', $_POST);
		build_sync_packet();
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
				$r = q("update account set account_salt = '%s', account_password = '%s', account_password_changed = '%s' 
					where account_id = %d",
					dbesc($salt),
					dbesc($password_encoded),
					dbesc(datetime_convert()),
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
		$account = $a->get_account();
		if($email != $account['account_email']) {
    	    if(! valid_email($email))
				$errs[] = t('Not valid email.');
			$adm = trim(get_config('system','admin_email'));
			if(($adm) && (strcasecmp($email,$adm) == 0)) {
				$errs[] = t('Protected email address. Cannot change to that email.');
				$email = $a->user['email'];
			}
			if(! $errs) {
				$r = q("update account set account_email = '%s' where account_id = %d",
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

	$set_perms = '';

	$role = ((x($_POST,'permissions_role')) ? notags(trim($_POST['permissions_role'])) : '');
	$oldrole = get_pconfig(local_channel(),'system','permissions_role');

	if(($role != $oldrole) || ($role === 'custom')) {

		if($role === 'custom') {
			$hide_presence    = (((x($_POST,'hide_presence')) && (intval($_POST['hide_presence']) == 1)) ? 1: 0);
			$publish          = (((x($_POST,'profile_in_directory')) && (intval($_POST['profile_in_directory']) == 1)) ? 1: 0);
			$def_group        = ((x($_POST,'group-selection')) ? notags(trim($_POST['group-selection'])) : '');
			$r = q("update channel set channel_default_group = '%s' where channel_id = %d",
				dbesc($def_group),
				intval(local_channel())
			);	

			$global_perms = get_perms();

			foreach($global_perms as $k => $v) {
				$set_perms .= ', ' . $v[0] . ' = ' . intval($_POST[$k]) . ' ';
			}

			$str_group_allow   = perms2str($_POST['group_allow']);
			$str_contact_allow = perms2str($_POST['contact_allow']);
			$str_group_deny    = perms2str($_POST['group_deny']);
			$str_contact_deny  = perms2str($_POST['contact_deny']);
			$r = q("update channel set channel_allow_cid = '%s', channel_allow_gid = '%s', channel_deny_cid = '%s', channel_deny_gid = '%s'
				where channel_id = %d",
				dbesc($str_contact_allow),
				dbesc($str_group_allow),
				dbesc($str_contact_deny),
				dbesc($str_group_deny),
				intval(local_channel())
			);
		}
	    else {
		   	$role_permissions = get_role_perms($_POST['permissions_role']);
			if(! $role_permissions) {
				notice('Permissions category could not be found.');
				return;
			}
			$hide_presence    = 1 - (intval($role_permissions['online']));
			if($role_permissions['default_collection']) {
				$r = q("select hash from groups where uid = %d and name = '%s' limit 1",
					intval(local_channel()),
					dbesc( t('Friends') )
				);
				if(! $r) {
					require_once('include/group.php');
					group_add(local_channel(), t('Friends'));
					group_add_member(local_channel(),t('Friends'),$channel['channel_hash']);
					$r = q("select hash from groups where uid = %d and name = '%s' limit 1",
						intval(local_channel()),
						dbesc( t('Friends') )
					);
				}
				if($r) {
					q("update channel set channel_default_group = '%s', channel_allow_gid = '%s', channel_allow_cid = '', channel_deny_gid = '', channel_deny_cid = '' where channel_id = %d",
						dbesc($r[0]['hash']),
						dbesc('<' . $r[0]['hash'] . '>'),
						intval(local_channel())
					);
				}
				else {
					notice( sprintf('Default privacy collection \'%s\' not found. Please create and re-submit permission change.', t('Friends')) . EOL);
					return;
				}
			}
			// no default collection
			else {
				q("update channel set channel_default_group = '', channel_allow_gid = '', channel_allow_cid = '', channel_deny_gid = '', 
					channel_deny_cid = '' where channel_id = %d",
						intval(local_channel())
				);
			}

			$r = q("update abook set abook_my_perms  = %d where abook_channel = %d and (abook_flags & %d)>0",
				intval(($role_permissions['perms_auto']) ? intval($role_permissions['perms_accept']) : 0),
				intval(local_channel()),
				intval(ABOOK_FLAG_SELF)
			);
			set_pconfig(local_channel(),'system','autoperms',(($role_permissions['perms_auto']) ? intval($role_permissions['perms_accept']) : 0));

			foreach($role_permissions as $p => $v) {
				if(strpos($p,'channel_') !== false) {
					$set_perms .= ', ' . $p . ' = ' . intval($v) . ' ';
				}
				if($p === 'directory_publish') {
					$publish = intval($v);
				}
			}
		}

		set_pconfig(local_channel(),'system','hide_online_status',$hide_presence);
		set_pconfig(local_channel(),'system','permissions_role',$role);
	}

	$username         = ((x($_POST,'username'))   ? notags(trim($_POST['username']))     : '');
	$timezone         = ((x($_POST,'timezone'))   ? notags(trim($_POST['timezone']))     : '');
	$defloc           = ((x($_POST,'defloc'))     ? notags(trim($_POST['defloc']))       : '');
	$openid           = ((x($_POST,'openid_url')) ? notags(trim($_POST['openid_url']))   : '');
	$maxreq           = ((x($_POST,'maxreq'))     ? intval($_POST['maxreq'])             : 0);
	$expire           = ((x($_POST,'expire'))     ? intval($_POST['expire'])             : 0);
	$evdays           = ((x($_POST,'evdays'))     ? intval($_POST['evdays'])             : 3);

	$channel_menu     = ((x($_POST['channel_menu'])) ? htmlspecialchars_decode(trim($_POST['channel_menu']),ENT_QUOTES) : '');

	$expire_items     = ((x($_POST,'expire_items')) ? intval($_POST['expire_items'])	 : 0);
	$expire_starred   = ((x($_POST,'expire_starred')) ? intval($_POST['expire_starred']) : 0);
	$expire_photos    = ((x($_POST,'expire_photos'))? intval($_POST['expire_photos'])	 : 0);
	$expire_network_only    = ((x($_POST,'expire_network_only'))? intval($_POST['expire_network_only'])	 : 0);

	$allow_location   = (((x($_POST,'allow_location')) && (intval($_POST['allow_location']) == 1)) ? 1: 0);


	$blocktags        = (((x($_POST,'blocktags')) && (intval($_POST['blocktags']) == 1)) ? 0: 1); // this setting is inverted!
	$unkmail          = (((x($_POST,'unkmail')) && (intval($_POST['unkmail']) == 1)) ? 1: 0);
	$cntunkmail       = ((x($_POST,'cntunkmail')) ? intval($_POST['cntunkmail']) : 0);
	$suggestme        = ((x($_POST,'suggestme')) ? intval($_POST['suggestme'])  : 0);  

	$post_newfriend   = (($_POST['post_newfriend'] == 1) ? 1: 0);
	$post_joingroup   = (($_POST['post_joingroup'] == 1) ? 1: 0);
	$post_profilechange   = (($_POST['post_profilechange'] == 1) ? 1: 0);
	$adult            = (($_POST['adult'] == 1) ? 1 : 0);

	$channel = $a->get_channel();
	$pageflags = $channel['channel_pageflags'];
	$existing_adult = (($pageflags & PAGE_ADULT) ? 1 : 0);
	if($adult != $existing_adult)
		$pageflags = ($pageflags ^ PAGE_ADULT);


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


	$vnotify = 0;

	if(x($_POST,'vnotify1'))
		$vnotify += intval($_POST['vnotify1']);
	if(x($_POST,'vnotify2'))
		$vnotify += intval($_POST['vnotify2']);
	if(x($_POST,'vnotify3'))
		$vnotify += intval($_POST['vnotify3']);
	if(x($_POST,'vnotify4'))
		$vnotify += intval($_POST['vnotify4']);
	if(x($_POST,'vnotify5'))
		$vnotify += intval($_POST['vnotify5']);
	if(x($_POST,'vnotify6'))
		$vnotify += intval($_POST['vnotify6']);
	if(x($_POST,'vnotify7'))
		$vnotify += intval($_POST['vnotify7']);
	if(x($_POST,'vnotify8'))
		$vnotify += intval($_POST['vnotify8']);
	if(x($_POST,'vnotify9'))
		$vnotify += intval($_POST['vnotify9']);
	if(x($_POST,'vnotify10'))
		$vnotify += intval($_POST['vnotify10']);
	if(x($_POST,'vnotify11'))
		$vnotify += intval($_POST['vnotify11']);

	$always_show_in_notices = x($_POST,'always_show_in_notices') ? 1 : 0;

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

	set_pconfig(local_channel(),'system','use_browser_location',$allow_location);
	set_pconfig(local_channel(),'system','suggestme', $suggestme);
	set_pconfig(local_channel(),'system','post_newfriend', $post_newfriend);
	set_pconfig(local_channel(),'system','post_joingroup', $post_joingroup);
	set_pconfig(local_channel(),'system','post_profilechange', $post_profilechange);
	set_pconfig(local_channel(),'system','blocktags',$blocktags);
	set_pconfig(local_channel(),'system','channel_menu',$channel_menu);
	set_pconfig(local_channel(),'system','vnotify',$vnotify);
	set_pconfig(local_channel(),'system','always_show_in_notices',$always_show_in_notices);
	set_pconfig(local_channel(),'system','evdays',$evdays);

	$r = q("update channel set channel_name = '%s', channel_pageflags = %d, channel_timezone = '%s', channel_location = '%s', channel_notifyflags = %d, channel_max_anon_mail = %d, channel_max_friend_req = %d, channel_expire_days = %d $set_perms where channel_id = %d",
		dbesc($username),
		intval($pageflags),
		dbesc($timezone),
		dbesc($defloc),
		intval($notify),
		intval($unkmail),
		intval($maxreq),
		intval($expire),
		intval(local_channel())
	);   
	if($r)
		info( t('Settings updated.') . EOL);

	if(! is_null($publish)) {
		$r = q("UPDATE profile SET publish = %d WHERE is_default = 1 AND uid = %d",
			intval($publish),
			intval(local_channel())
		);
	}

	if($name_change) {
		$r = q("update xchan set xchan_name = '%s', xchan_name_date = '%s' where xchan_hash = '%s'",
			dbesc($username),
			dbesc(datetime_convert()),
			dbesc($channel['channel_hash'])
		);
		$r = q("update profile set name = '%s' where uid = %d and is_default = 1",
			dbesc($username),
			intval($channel['channel_id'])
		);
	}

	proc_run('php','include/directory.php',local_channel());

	build_sync_packet();


	//$_SESSION['theme'] = $theme;
	if($email_changed && $a->config['system']['register_policy'] == REGISTER_VERIFY) {

		// FIXME - set to un-verified, blocked and redirect to logout
		// Why? Are we verifying people or email addresses?

	}

	goaway($a->get_baseurl(true) . '/settings' );
	return; // NOTREACHED
}
		


function settings_content(&$a) {

	$o = '';
	nav_set_selected('settings');


	if((! local_channel()) || ($_SESSION['delegate'])) {
		notice( t('Permission denied.') . EOL );
		return login();
	}


	$channel = $a->get_channel();
	if($channel)
		head_set_icon($channel['xchan_photo_s']);

	$yes_no = array(t('No'),t('Yes'));
		
	if((argc() > 1) && (argv(1) === 'oauth')) {
		
		if((argc() > 2) && (argv(2) === 'add')) {
			$tpl = get_markup_template("settings_oauth_edit.tpl");
			$o .= replace_macros($tpl, array(
				'$form_security_token' => get_form_security_token("settings_oauth"),
				'$title'	=> t('Add application'),
				'$submit'	=> t('Submit'),
				'$cancel'	=> t('Cancel'),
				'$name'		=> array('name', t('Name'), '', t('Name of application')),
				'$key'		=> array('key', t('Consumer Key'), random_string(16), t('Automatically generated - change if desired. Max length 20')),
				'$secret'	=> array('secret', t('Consumer Secret'), random_string(16), t('Automatically generated - change if desired. Max length 20')),
				'$redirect'	=> array('redirect', t('Redirect'), '', t('Redirect URI - leave blank unless your application specifically requires this')),
				'$icon'		=> array('icon', t('Icon url'), '', t('Optional')),
			));
			return $o;
		}
		
		if((argc() > 3) && (argv(2) === 'edit')) {
			$r = q("SELECT * FROM clients WHERE client_id='%s' AND uid=%d",
					dbesc(argv(3)),
					local_channel());
			
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
					local_channel());
			goaway($a->get_baseurl(true)."/settings/oauth/");
			return;			
		}
		
		
		$r = q("SELECT clients.*, tokens.id as oauth_token, (clients.uid=%d) AS my 
				FROM clients
				LEFT JOIN tokens ON clients.client_id=tokens.client_id
				WHERE clients.uid IN (%d,0)",
				local_channel(),
				local_channel());
		
		
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

		$o = '';
		$diaspora_enabled = get_config('system','diaspora_enabled');
		
		$r = q("SELECT * FROM `hook` WHERE `hook` = 'feature_settings' ");
		if((! $r) && (! $diaspora_enabled))
			$settings_addons = t('No feature settings configured');

		if($diaspora_enabled) {
			$pubcomments = get_pconfig(local_channel(),'system','diaspora_public_comments');
			if($pubcomments === false)
				$pubcomments = 1;
			$hijacking = get_pconfig(local_channel(),'system','prevent_tag_hijacking');


		}

		call_hooks('feature_settings', $settings_addons);
				
		$tpl = get_markup_template("settings_addons.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_featured"),
			'$title'	=> t('Feature/Addon Settings'),
			'$diaspora_enabled' => $diaspora_enabled,
			'$dsprdesc' => t('Settings for the built-in Diaspora emulator'), 
			'$pubcomments' => array('dspr_pubcomment', t('Allow any Diaspora member to comment on your public posts'), $pubcomments, '', $yes_no),
			'$dsprtitle' => t('Diaspora Policy Settings'),
			'$hijacking' => array('dspr_hijack', t('Prevent your hashtags from being redirected to other sites'), $hijacking, '', $yes_no),
			'$dsprsubmit' => t('Submit'),
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
			'$password1'=> array('npassword', t('Enter New Password:'), '', ''),
			'$password2'=> array('confirm', t('Confirm New Password:'), '', t('Leave password fields blank unless changing')),
			'$submit' 	=> t('Submit'),
			'$email' 	=> array('email', t('Email Address:'), $email, ''),
			'$removeme' => t('Remove Account'),
			'$removeaccount' => t('Remove this account including all its channels'),
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
				$arr[$fname][1][] = array('feature_' .$f[0],$f[1],((intval(feature_enabled(local_channel(),$f[0]))) ? "1" : ''),$f[2],array(t('Off'),t('On')));
			}
		}
		
		$tpl = get_markup_template("settings_features.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_features"),
			'$title'	=> t('Additional Features'),
			'$features' => $arr,
			'$submit'   => t('Submit'),
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
		$default_mobile_theme = get_config('system','mobile_theme');
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
		$files = glob('view/theme/*');
		if($allowed_themes) {
			foreach($allowed_themes as $th) {
				$f = $th;
				$is_experimental = file_exists('view/theme/' . $th . '/experimental');
				$unsupported = file_exists('view/theme/' . $th . '/unsupported');
				$is_mobile = file_exists('view/theme/' . $th . '/mobile');
				$is_library = file_exists('view/theme/'. $th . '/library');
				$mobile_themes["---"] = t("No special theme for mobile devices");

				if (!$is_experimental or ($is_experimental && (get_config('experimentals','exp_themes')==1 or get_config('experimentals','exp_themes')===false))){ 
					$theme_name = (($is_experimental) ?  sprintf(t('%s - (Experimental)'), $f) : $f);
					if (! $is_library) {
						if($is_mobile) {
							$mobile_themes[$f] = $themes[$f] = $theme_name . ' (' . t('mobile') . ')';
						}
						else {
							$mobile_themes[$f] = $themes[$f] = $theme_name;
						}
					}
				}

			}
		}
		$theme_selected = (!x($_SESSION,'theme')? $default_theme : $_SESSION['theme']);
		$mobile_theme_selected = (!x($_SESSION,'mobile_theme')? $default_mobile_theme : $_SESSION['mobile_theme']);

		$user_scalable = get_pconfig(local_channel(),'system','user_scalable');
		$user_scalable = (($user_scalable===false)? '1': $user_scalable); // default if not set: 1
		
		$browser_update = intval(get_pconfig(local_channel(), 'system','update_interval'));
		$browser_update = (($browser_update == 0) ? 80 : $browser_update / 1000); // default if not set: 40 seconds

		$itemspage = intval(get_pconfig(local_channel(), 'system','itemspage'));
		$itemspage = (($itemspage > 0 && $itemspage < 101) ? $itemspage : 20); // default if not set: 20 items
		
		$nosmile = get_pconfig(local_channel(),'system','no_smilies');
		$nosmile = (($nosmile===false)? '0': $nosmile); // default if not set: 0

		$title_tosource = get_pconfig(local_channel(),'system','title_tosource');
		$title_tosource = (($title_tosource===false)? '0': $title_tosource); // default if not set: 0

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
			'$uid' => local_channel(),
		
			'$theme'	=> (($themes) ? array('theme', t('Display Theme:'), $theme_selected, '', $themes, 'preview') : false),
			'$mobile_theme'	=> (($mobile_themes) ? array('mobile_theme', t('Mobile Theme:'), $mobile_theme_selected, '', $mobile_themes, '') : false),
			'$user_scalable' => array('user_scalable', t("Enable user zoom on mobile devices"), $user_scalable, '', $yes_no),
			'$ajaxint'   => array('browser_update',  t("Update browser every xx seconds"), $browser_update, t('Minimum of 10 seconds, no maximum')),
			'$itemspage'   => array('itemspage',  t("Maximum number of conversations to load at any time:"), $itemspage, t('Maximum of 100 items')),
			'$nosmile'	=> array('nosmile', t("Show emoticons (smilies) as images"), 1-intval($nosmile), '', $yes_no),
			'$title_tosource'	=> array('title_tosource', t("Link post titles to source"), $title_tosource, '', $yes_no),
			'$layout_editor' => t('System Page Layout Editor - (advanced)'),
			'$theme_config' => $theme_config,
			'$expert' => feature_enabled(local_channel(),'expert'),
			'$channel_list_mode' => array('channel_list_mode', t('Use blog/list mode on channel page'), get_pconfig(local_channel(),'system','channel_list_mode'), t('(comments displayed separately)'), $yes_no),
			'$network_list_mode' => array('network_list_mode', t('Use blog/list mode on matrix page'), get_pconfig(local_channel(),'system','network_list_mode'), t('(comments displayed separately)'), $yes_no),
			'$channel_divmore_height' => array('channel_divmore_height', t('Channel page max height of content (in pixels)'), ((get_pconfig(local_channel(),'system','channel_divmore_height')) ? get_pconfig(local_channel(),'system','channel_divmore_height') : 400), t('click to expand content exceeding this height')),
			'$network_divmore_height' => array('network_divmore_height', t('Matrix page max height of content (in pixels)'), ((get_pconfig(local_channel(),'system','network_divmore_height')) ? get_pconfig(local_channel(),'system','network_divmore_height') : 400) , t('click to expand content exceeding this height')),


		));
		
		return $o;
	}
	
	



	if(argv(1) === 'channel') {

		require_once('include/acl_selectors.php');
		require_once('include/permissions.php');


		$p = q("SELECT * FROM `profile` WHERE `is_default` = 1 AND `uid` = %d LIMIT 1",
			intval(local_channel())
		);
		if(count($p))
			$profile = $p[0];

		load_pconfig(local_channel(),'expire');

		$channel = $a->get_channel();


		$global_perms = get_perms();

		$permiss = array();

		$perm_opts = array(
			array( t('Nobody except yourself'), 0),
			array( t('Only those you specifically allow'), PERMS_SPECIFIC), 
			array( t('Approved connections'), PERMS_CONTACTS),
			array( t('Any connections'), PERMS_PENDING),
			array( t('Anybody on this website'), PERMS_SITE),
			array( t('Anybody in this network'), PERMS_NETWORK),
			array( t('Anybody authenticated'), PERMS_AUTHED),
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
		$adult_flag = intval($channel['channel_pageflags'] & PAGE_ADULT);

//		$unkmail    = $a->user['unkmail'];
//		$cntunkmail = $a->user['cntunkmail'];

		$hide_presence = intval(get_pconfig(local_channel(), 'system','hide_online_status'));


		$expire_items = get_pconfig(local_channel(), 'expire','items');
		$expire_items = (($expire_items===false)? '1' : $expire_items); // default if not set: 1
	
		$expire_notes = get_pconfig(local_channel(), 'expire','notes');
		$expire_notes = (($expire_notes===false)? '1' : $expire_notes); // default if not set: 1

		$expire_starred = get_pconfig(local_channel(), 'expire','starred');
		$expire_starred = (($expire_starred===false)? '1' : $expire_starred); // default if not set: 1
	
		$expire_photos = get_pconfig(local_channel(), 'expire','photos');
		$expire_photos = (($expire_photos===false)? '0' : $expire_photos); // default if not set: 0

		$expire_network_only = get_pconfig(local_channel(), 'expire','network_only');
		$expire_network_only = (($expire_network_only===false)? '0' : $expire_network_only); // default if not set: 0


		$suggestme = get_pconfig(local_channel(), 'system','suggestme');
		$suggestme = (($suggestme===false)? '0': $suggestme); // default if not set: 0

		$post_newfriend = get_pconfig(local_channel(), 'system','post_newfriend');
		$post_newfriend = (($post_newfriend===false)? '0': $post_newfriend); // default if not set: 0

		$post_joingroup = get_pconfig(local_channel(), 'system','post_joingroup');
		$post_joingroup = (($post_joingroup===false)? '0': $post_joingroup); // default if not set: 0

		$post_profilechange = get_pconfig(local_channel(), 'system','post_profilechange');
		$post_profilechange = (($post_profilechange===false)? '0': $post_profilechange); // default if not set: 0

		$blocktags  = get_pconfig(local_channel(),'system','blocktags');
		$blocktags = (($blocktags===false) ? '0' : $blocktags);
	
		$timezone = date_default_timezone_get();

		$opt_tpl = get_markup_template("field_checkbox.tpl");
		if(get_config('system','publish_all')) {
			$profile_in_dir = '<input type="hidden" name="profile_in_directory" value="1" />';
		}
		else {
			$profile_in_dir = replace_macros($opt_tpl,array(
				'$field' 	=> array('profile_in_directory', t('Publish your default profile in the network directory'), $profile['publish'], '', $yes_no),
			));
		}

		$suggestme = replace_macros($opt_tpl,array(
				'$field' 	=> array('suggestme',  t('Allow us to suggest you as a potential friend to new members?'), $suggestme, '', $yes_no),

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

		$celeb = false;

		$perm_defaults = array(
			'allow_cid' => $channel['channel_allow_cid'], 
			'allow_gid' => $channel['channel_allow_gid'], 
			'deny_cid' => $channel['channel_deny_cid'], 
			'deny_gid' => $channel['channel_deny_gid']
		); 


		require_once('include/group.php');
		$group_select = mini_group_select(local_channel(),$channel['channel_default_group']);

		require_once('include/menu.php');
		$m1 = menu_list(local_channel());
		$menu = false;
		if($m1) {
			$menu = array();
			$current = get_pconfig(local_channel(),'system','channel_menu');
			$menu[] = array('name' => '', 'selected' => ((! $current) ? true : false));
			foreach($m1 as $m) {
				$menu[] = array('name' => htmlspecialchars($m['menu_name'],ENT_COMPAT,'UTF-8'), 'selected' => (($m['menu_name'] === $current) ? ' selected="selected" ' : false));
			}
		}

		$evdays = get_pconfig(local_channel(),'system','evdays');
		if(! $evdays)
			$evdays = 3;

		$permissions_role = get_pconfig(local_channel(),'system','permissions_role');
		if(! $permissions_role)
			$permissions_role = 'custom';

		$permissions_set = (($permissions_role != 'custom') ? true : false);
		$vnotify = get_pconfig(local_channel(),'system','vnotify');
		$always_show_in_notices = get_pconfig(local_channel(),'system','always_show_in_notices');
		if($vnotify === false)
			$vnotify = (-1);

		$o .= replace_macros($stpl,array(
			'$ptitle' 	=> t('Channel Settings'),

			'$submit' 	=> t('Submit'),
			'$baseurl' => $a->get_baseurl(true),
			'$uid' => local_channel(),
			'$form_security_token' => get_form_security_token("settings"),
			'$nickname_block' => $prof_addr,
			'$h_basic' 	=> t('Basic Settings'),
			'$username' => array('username',  t('Full Name:'), $username,''),
			'$email' 	=> array('email', t('Email Address:'), $email, ''),
			'$timezone' => array('timezone_select' , t('Your Timezone:'), $timezone, '', get_timezones()),
			'$defloc'	=> array('defloc', t('Default Post Location:'), $defloc, t('Geographical location to display on your posts')),
			'$allowloc' => array('allow_location', t('Use Browser Location:'), ((get_pconfig(local_channel(),'system','use_browser_location')) ? 1 : ''), '', $yes_no),
		
			'$adult'    => array('adult', t('Adult Content'), $adult_flag, t('This channel frequently or regularly publishes adult content. (Please tag any adult material and/or nudity with #NSFW)'), $yes_no),

			'$h_prv' 	=> t('Security and Privacy Settings'),
			'$permissions_set' => $permissions_set,
			'$perms_set_msg' => t('Your permissions are already configured. Click to view/adjust'),

			'$hide_presence' => array('hide_presence', t('Hide my online presence'),$hide_presence, t('Prevents displaying in your profile that you are online'), $yes_no),

			'$lbl_pmacro' => t('Simple Privacy Settings:'),
			'$pmacro3'    => t('Very Public - <em>extremely permissive (should be used with caution)</em>'),
			'$pmacro2'    => t('Typical - <em>default public, privacy when desired (similar to social network permissions but with improved privacy)</em>'),
			'$pmacro1'    => t('Private - <em>default private, never open or public</em>'),
			'$pmacro0'    => t('Blocked - <em>default blocked to/from everybody</em>'),
			'$permiss_arr' => $permiss,
			'$blocktags' => array('blocktags',t('Allow others to tag your posts'), 1-$blocktags, t('Often used by the community to retro-actively flag inappropriate content'), $yes_no),

			'$lbl_p2macro' => t('Advanced Privacy Settings'),

			'$expire' => array('expire',t('Expire other channel content after this many days'),$expire,t('0 or blank prevents expiration')),
			'$maxreq' 	=> array('maxreq', t('Maximum Friend Requests/Day:'), intval($channel['channel_max_friend_req']) , t('May reduce spam activity')),
			'$permissions' => t('Default Post Permissions'),
			'$permdesc' => t("\x28click to open/close\x29"),
			'$aclselect' => populate_acl($perm_defaults,false),
			'$suggestme' => $suggestme,
			'$group_select' => $group_select,
			'$role' => array('permissions_role' , t('Channel permissions category:'), $permissions_role, '', get_roles()),

			'$profile_in_dir' => $profile_in_dir,
			'$hide_friends' => $hide_friends,
			'$hide_wall' => $hide_wall,
			'$unkmail' => $unkmail,		
			'$cntunkmail' 	=> array('cntunkmail', t('Maximum private messages per day from unknown people:'), intval($channel['channel_max_anon_mail']) ,t("Useful to reduce spamming")),
		
		
			'$h_not' 	=> t('Notification Settings'),
			'$activity_options' => t('By default post a status message when:'),
			'$post_newfriend' => array('post_newfriend',  t('accepting a friend request'), $post_newfriend, '', $yes_no),
			'$post_joingroup' => array('post_joingroup',  t('joining a forum/community'), $post_joingroup, '', $yes_no),
			'$post_profilechange' => array('post_profilechange',  t('making an <em>interesting</em> profile change'), $post_profilechange, '', $yes_no),
			'$lbl_not' 	=> t('Send a notification email when:'),
			'$notify1'	=> array('notify1', t('You receive a connection request'), ($notify & NOTIFY_INTRO), NOTIFY_INTRO, '', $yes_no),
			'$notify2'	=> array('notify2', t('Your connections are confirmed'), ($notify & NOTIFY_CONFIRM), NOTIFY_CONFIRM, '', $yes_no),
			'$notify3'	=> array('notify3', t('Someone writes on your profile wall'), ($notify & NOTIFY_WALL), NOTIFY_WALL, '', $yes_no),
			'$notify4'	=> array('notify4', t('Someone writes a followup comment'), ($notify & NOTIFY_COMMENT), NOTIFY_COMMENT, '', $yes_no),
			'$notify5'	=> array('notify5', t('You receive a private message'), ($notify & NOTIFY_MAIL), NOTIFY_MAIL, '', $yes_no),
			'$notify6'  => array('notify6', t('You receive a friend suggestion'), ($notify & NOTIFY_SUGGEST), NOTIFY_SUGGEST, '', $yes_no),
			'$notify7'  => array('notify7', t('You are tagged in a post'), ($notify & NOTIFY_TAGSELF), NOTIFY_TAGSELF, '', $yes_no),
			'$notify8'  => array('notify8', t('You are poked/prodded/etc. in a post'), ($notify & NOTIFY_POKE), NOTIFY_POKE, '', $yes_no),
		

			'$lbl_vnot' 	=> t('Show visual notifications including:'),

			'$vnotify1'	=> array('vnotify1', t('Unseen matrix activity'), ($vnotify & VNOTIFY_NETWORK), VNOTIFY_NETWORK, '', $yes_no),
			'$vnotify2'	=> array('vnotify2', t('Unseen channel activity'), ($vnotify & VNOTIFY_CHANNEL), VNOTIFY_CHANNEL, '', $yes_no),
			'$vnotify3'	=> array('vnotify3', t('Unseen private messages'), ($vnotify & VNOTIFY_MAIL), VNOTIFY_MAIL, t('Recommended'), $yes_no),
			'$vnotify4'	=> array('vnotify4', t('Upcoming events'), ($vnotify & VNOTIFY_EVENT), VNOTIFY_EVENT, '', $yes_no),
			'$vnotify5'	=> array('vnotify5', t('Events today'), ($vnotify & VNOTIFY_EVENTTODAY), VNOTIFY_EVENTTODAY, '', $yes_no),
			'$vnotify6'  => array('vnotify6', t('Upcoming birthdays'), ($vnotify & VNOTIFY_BIRTHDAY), VNOTIFY_BIRTHDAY, t('Not available in all themes'), $yes_no),
			'$vnotify7'  => array('vnotify7', t('System (personal) notifications'), ($vnotify & VNOTIFY_SYSTEM), VNOTIFY_SYSTEM, '', $yes_no),
			'$vnotify8'  => array('vnotify8', t('System info messages'), ($vnotify & VNOTIFY_INFO), VNOTIFY_INFO, t('Recommended'), $yes_no),
			'$vnotify9'  => array('vnotify9', t('System critical alerts'), ($vnotify & VNOTIFY_ALERT), VNOTIFY_ALERT, t('Recommended'), $yes_no),
			'$vnotify10'  => array('vnotify10', t('New connections'), ($vnotify & VNOTIFY_INTRO), VNOTIFY_INTRO, t('Recommended'), $yes_no),
			'$vnotify11'  => array('vnotify11', t('System Registrations'), ($vnotify & VNOTIFY_REGISTER), VNOTIFY_REGISTER, '', $yes_no),
			'$always_show_in_notices'  => array('always_show_in_notices', t('Also show new wall posts, private messages and connections under Notices'), $always_show_in_notices, 1, '', $yes_no),

			'$evdays' => array('evdays', t('Notify me of events this many days in advance'), $evdays, t('Must be greater than 0')),			

			'$h_advn' => t('Advanced Account/Page Type Settings'),
			'$h_descadvn' => t('Change the behaviour of this account for special situations'),
			'$pagetype' => $pagetype,
			'$expert' => feature_enabled(local_channel(),'expert'),
			'$hint' => t('Please enable expert mode (in <a href="settings/features">Settings > Additional features</a>) to adjust!'),
			'$lbl_misc' => t('Miscellaneous Settings'),
			'$menus' => $menu,
			'$menu_desc' => t('Personal menu to display in your channel pages'),
			'$removeme' => t('Remove Channel'),
			'$removechannel' => t('Remove this channel.'),
		));

		call_hooks('settings_form',$o);

		$o .= '</form>' . "\r\n";

		return $o;
	}
}

