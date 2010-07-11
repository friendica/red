<?php



function group_init(&$a) {
	require_once('include/group.php');

}



function group_post(&$a) {

	if(! local_user()) {
		notice("Access denied." . EOL);
		return;
	}

	if(($a->argc == 2) && ($a->argv[1] == 'new')) {
		$name = notags(trim($_POST['groupname']));
		$r = group_add($_SESSION['uid'],$name);
		if($r) {
			notice("Group created." . EOL );
			$r = group_byname($_SESSION['uid'],$name);
			if($r)
				goaway($a->get_baseurl() . '/group/' . $r);
		}
		else
			notice("Could not create group." . EOL );	
//		goaway($a->get_baseurl() . '/group');
		return; // NOTREACHED
	}

}

function group_content(&$a) {

	if(! local_user()) {
		notice("Access denied." . EOL);
		return;
	}

	if(($a->argc == 2) && ($a->argv[1] == 'new')) {
		$tpl = file_get_contents('view/group_new.tpl');
		$o .= replace_macros($tpl,array(

		));




	}
		
	return $o;

}