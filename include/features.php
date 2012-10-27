<?php

/*
 * Features management
 */


function feature_enabled($uid,$feature) {
	$x = get_pconfig($uid,'feature',$feature);
	$arr = array('uid' => $uid, 'feature' => $feature, 'enabled' => $x);
	call_hooks('feature_enabled',$arr);
	return($arr['enabled']);
}

function get_features() {

$arr = array(

'multi_delete'   => t('Multiple Deletion'),
'expire'         => t('Content Expiration'),
'commtag'        => t('Community Tagging'),
'categories'     => t('Post Categories'),
'filing'         => t('Saved Folders'),
'archives'       => t('Search by Date'),


);

	call_hooks('get_features',$arr);
	return $arr;
}