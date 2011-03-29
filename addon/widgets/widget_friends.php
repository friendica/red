<?php

function widget_help(&$a, &$o, $conf) {
	$o .= "Shows profile contacts";
}

function widget_args(){
	return Array();
}

function widget_content(&$a, &$o, $conf){

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