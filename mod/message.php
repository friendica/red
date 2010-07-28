<?php

require_once('view/acl_selectors.php');

function message_init(&$a) {


}







function message_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if(($a->argc > 1) && ($a->argv[1] == 'new')) {
		
		$tpl = file_get_contents('view/jot-header.tpl');
	
		$a->page['htmlhead'] .= replace_macros($tpl, array('$baseurl' => $a->get_baseurl()));

		$select .= contact_select('messageto','message-to-select');
		$tpl = file_get_contents('view/prv_message.tpl');
		$o = replace_macros($tpl,array(
			'$select' => $select

		));

		return $o;
	}

	if($a->argc == 1) {

		$r = q("SELECT * FROM `mail` WHERE `seen` = 0 AND `uid` = %d LIMIT %d , %d ",
			intval($_SESSION['uid']),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);
		if(! count($r)) {
			notice( t('No messages.') . EOL);
			return;
		}


	}


}