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

array('multi_delete', t('Multiple Deletion'),  t('Select and delete multiple posts/comments at once')),
array('expire',       t('Content Expiration'), t('Remove old posts/comments after a period of time')),
array('commtag',      t('Community Tagging'),  t('Tag existing posts and share the links')),
array('categories',   t('Post Categories'),    t('Add categories to your channel postings')),
array('filing',       t('Saved Folders'),      t('Ability to file posts under easily remembered names')),
array('archives',     t('Search by Date'),     t('Select posts by date ranges')),
array('dislike',      t('Dislike Posts'),      t('Ability to dislike posts/comments')),
array('savedsearch',  t('Saved Searches'),     t('Save search terms for re-use')),
array('preview',      t('Post Preview'),       t('Preview posts and comments before publishing them')),
array('edit_posts',   t('Edit Sent Posts'),    t('Edit/correct posts and comments after sending')),
array('richtext',     t('Richtext Editor'),    t('Use richtext/visual editor where applicable')),
);

	call_hooks('get_features',$arr);
	return $arr;
}