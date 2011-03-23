<?php
	/**
	 * widgets from friendika
	 * 
	 * allow to embed info from friendika into another site
	 */
	 
	 
function widgets_install() {
	//  we need some hooks, for the configuration and for sending tweets
	register_hook('plugin_settings', 'addon/widgets/widgets.php', 'widgets_settings'); 
	register_hook('plugin_settings_post', 'addon/widgets/widgets.php', 'widgets_settings_post');

	logger("installed widgets");
}

function widgets_settings_post(){
	
	if (isset($_POST['widgets-submit'])){
		set_pconfig(local_user(), 'widgets', 'site', $_POST['widgets-site']);
		set_pconfig(local_user(), 'widgets', 'key', $_POST['widgets-key']);
	}
}

function widgets_settings(&$a,&$o) {
    if(! local_user())
		return;		
	
	$key    = get_pconfig(local_user(), 'widgets', 'key' );
	$site    = get_pconfig(local_user(), 'widgets', 'site' );

	if ($key=='') $key = mt_rand(); 

	$o .='
	<h3 class="settings-heading">Widgets</h3>
	<div id="settings-username-wrapper">
		<label for="widgets-site" id="settings-username-label">'.t('Remote site: ').'</label>
		<input type="text" value="'.$site.'" id="settings-username" name="widgets-site">
	</div>
	<div id="settings-username-end"></div>
	<div id="settings-username-wrapper">
		<label for="widgets-key" id="settings-username-label">'.t('Widgets key: ').'</label>
		<input type="hidden" value="'.$key.'" id="settings-username" name="widgets-key">
		<strong>'.$key.'</strong>
	</div>
	<div id="settings-username-end"></div>
	
	
		
	<div class="settings-submit-wrapper">
		<input type="submit" value="'.t('Submit').'" class="settings-submit" name="widgets-submit">
	</div>	
	';
	
	if ($key!='' and $site!='') {
		$o.='<h4>Widgets:</h4>
		<ul>
			<li><a href="'.$a->get_baseurl().'/widgets/friends/?p=1&k='.$key.'">Friend list</a></li>
		</ul>
		';
	}

}

function widgets_module() {
	return;
}

function _abs_url($s){
	$a = get_app();
	return preg_replace("|href=(['\"])([^h][^t][^t][^p])|", "href=\$1".$a->get_baseurl()."/\$2", $s);
}


function widgets_content(&$a) {

	if (!isset($_GET['k'])) {
		if($a->argv[2]=="cb"){header('HTTP/1.0 400 Bad Request'); killme();}
		return;
	}

	$r = q("SELECT * FROM pconfig WHERE uid IN (SELECT uid FROM pconfig  WHERE v='%s')AND  cat='widgets'",
			dbesc($_GET['k'])
		 );
	if (!count($r)){
		if($a->argv[2]=="cb"){header('HTTP/1.0 400 Bad Request'); killme();}
		return;
	}    
	$conf = array();
	$conf['uid'] = $r[0]['uid'];
	foreach($r as $e) { $conf[$e['k']]=$e['v']; }

	$o = "";	

//	echo "<pre>"; var_dump($a->argv); die();
	if ($a->argv[2]=="cb"){
		switch($a->argv[1]) {
			case 'friends': 
				widget_friends_content($a, $o, $conf);
				break;
		}
		
	} else {

		
		if (isset($_GET['p'])) {
			$o .= "<style>.f9k_widget { float: left;border:1px solid black; }</style>";
			$o .= "<h1>Preview Widget</h1>";
			$o .= '<a href="'.$a->get_baseurl().'/settings/addon">'. t("Plugin Settings") .'</a>';
			$o .= "<br style='clear:left'/><br/>";
			$o .= "<script>";
		} else {
			header("content-type: application/x-javascript");
		}
	
	

	
		$script = file_get_contents(dirname(__file__)."/widgets.js");
		$o .= replace_macros($script, array(
			'$entrypoint' => $a->get_baseurl()."/widgets/".$a->argv[1]."/cb/",
			'$key' => $conf['key'],
			'$widget_id' => 'f9k_'.$a->argv[1]."_".time(),
			'$loader' => $a->get_baseurl()."/images/rotator.gif",
		));

	
		if (isset($_GET['p'])) {
			$o .= "</script>
			<br style='clear:left'/><br/>
			<h4>Copy and paste this code</h4>
			<code>"
			
			.htmlspecialchars('<script src="'.$a->get_baseurl().'/widgets/'.$a->argv[1].'?k='.$conf['key'].'"></script>')
			."</code>";
			return $o;
		}	
		
	}	
	
	echo $o;
	killme();
}


function widget_friends_content(&$a, &$o, $conf){
	if (!local_user()){
		if (!isset($_GET['s']))
			header('HTTP/1.0 400 Bad Request');
		
		if (substr($_GET['s'],0,strlen($conf['site'])) !== $conf['site'])
			header('HTTP/1.0 400 Bad Request');
	} 
	$r = q("SELECT `profile`.`uid` AS `profile_uid`, `profile`.* , `user`.* FROM `profile` 
			LEFT JOIN `user` ON `profile`.`uid` = `user`.`uid`
			WHERE `user`.`uid` = %s AND `profile`.`is-default` = 1 LIMIT 1",
			intval($conf['uid'])
	);
	
	if(!count($r)) return;
	$a->profile = $r[0];

	$o .= "<style>
		.f9k_widget .contact-block-div { display: block !important; float: left!important; width: 50px!important; height: 50px!important; margin: 2px!important;}
		.f9k_widget #contact-block-end { clear: left; }
	</style>";
	$o .= _abs_url(contact_block());
	$o .= "<a href='".$a->get_baseurl().'/profile/'.$a->profile['nickname']."'>". t('Connect on Friendika!') ."</a>";
	

}
 
?>
