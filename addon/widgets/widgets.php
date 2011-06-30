<?php
/**
 * Name: Widgets
 * Description: Allow to embed info from friendika into another site
 * Version: 1.0
 * Author: Fabio Comuni <http://kirgroup.com/profile/fabrix/>
 */
 
	 
function widgets_install() {
	register_hook('plugin_settings', 'addon/widgets/widgets.php', 'widgets_settings'); 
	register_hook('plugin_settings_post', 'addon/widgets/widgets.php', 'widgets_settings_post');
	logger("installed widgets");
}
function widgets_uninstall() {
	unregister_hook('plugin_settings', 'addon/widgets/widgets.php', 'widgets_settings'); 
	unregister_hook('plugin_settings_post', 'addon/widgets/widgets.php', 'widgets_settings_post');
}


function widgets_settings_post(){
	
	if (isset($_POST['widgets-submit'])){
		del_pconfig(local_user(), 'widgets', 'key');
		
	}
}

function widgets_settings(&$a,&$o) {
    if(! local_user())
		return;		
	
	
	$key = get_pconfig(local_user(), 'widgets', 'key' );
	if ($key=='') { $key = mt_rand(); set_pconfig(local_user(), 'widgets', 'key', $key); }

	$widgets = array();
	$d = dir(dirname(__file__));
	while(false !== ($f = $d->read())) {
		 if(substr($f,0,7)=="widget_") {
			 preg_match("|widget_([^.]+).php|", $f, $m);
			 $w=$m[1];
			 require_once($f);
			 $widgets[] = array($w, call_user_func($w."_widget_name"));

		 }
	}

	
	
	$t = file_get_contents( dirname(__file__). "/settings.tpl" );
	$o .= replace_macros($t, array(
		'$submit' => t('Generate new key'),
		'$baseurl' => $a->get_baseurl(),
		'$title' => "Widgets",
		'$label' => t('Widgets key'),
		'$key' => $key,
		'$widgets_h' => t('Widgets available'),
		'$widgets' => $widgets,
	));
	
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

	$widgetfile =dirname(__file__)."/widget_".$a->argv[1].".php";
	if (file_exists($widgetfile)){
		require_once($widgetfile);
	} else {
		if($a->argv[2]=="cb"){header('HTTP/1.0 400 Bad Request'); killme();}
		return;
	}		
	



	//echo "<pre>"; var_dump($a->argv); die();
	if ($a->argv[2]=="cb"){
		/*if (!local_user()){
			if (!isset($_GET['s']))
				{header('HTTP/1.0 400 Bad Request'); killme();}
			
			if (substr($_GET['s'],0,strlen($conf['site'])) !== $conf['site'])
				{header('HTTP/1.0 400 Bad Request'); killme();}
		} */
		$o .= call_user_func($a->argv[1].'_widget_content',$a, $conf);
		
	} else {

		
		if (isset($_GET['p']) && local_user()==$conf['uid'] ) {
			$o .= "<style>.f9k_widget { float: left;border:1px solid black; }</style>";
			$o .= "<h1>Preview Widget</h1>";
			$o .= '<a href="'.$a->get_baseurl().'/settings/addon">'. t("Plugin Settings") .'</a>';

			$o .=  "<h4>".call_user_func($a->argv[1].'_widget_name')."</h4>";
			$o .=  call_user_func($a->argv[1].'_widget_help');
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
			'$args' => (isset($_GET['a'])?$_GET['a']:''),
		));

	
		if (isset($_GET['p'])) {
			$jsargs = implode("</em>,<em>", call_user_func($a->argv[1].'_widget_args'));
			if ($jsargs!='') $jsargs = "&a=<em>".$jsargs."</em>";
				
			$o .= "</script>
			<br style='clear:left'/><br/>
			<h4>Copy and paste this code</h4>
			<code>"
			
			.htmlspecialchars('<script src="'.$a->get_baseurl().'/widgets/'.$a->argv[1].'?k='.$conf['key'])
			.$jsargs
			.htmlspecialchars('"></script>')
			."</code>";
			return $o;
		}	
		
	}	
	
	echo $o;
	killme();
}



 
?>
