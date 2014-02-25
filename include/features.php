<?php /** @file */

/*
 * Features management
 */


function feature_enabled($uid,$feature) {
	$x = get_pconfig($uid,'feature',$feature);
	if($x === false)
		$x = get_config('feature',$feature);
	$arr = array('uid' => $uid, 'feature' => $feature, 'enabled' => $x);
	call_hooks('feature_enabled',$arr);
	return($arr['enabled']);
}

function get_features() {

	$arr = array(

		// General
		'general' => array(
			t('General Features'),
// This is per post, and different from fixed expiration 'expire' which isn't working yet
			array('content_expire',         t('Content Expiration'),		t('Remove posts/comments and/or private messages at a future time')),
			array('multi_profiles', t('Multiple Profiles'),			t('Ability to create multiple profiles')),
			array('webpages',       t('Web Pages'),                 t('Provide managed web pages on your channel')),
			array('private_notes',  t('Private Notes'),             t('Enables a tool to store notes and reminders')),
// prettyphoto has licensing issues and will no longer be provided in core - 
// in any event this setting should probably be a theme option or plugin 
//			array('prettyphoto',       t('Enhanced Photo Albums'),                 t('Enable photo album with enhanced features')),
			//FIXME - needs a description, but how the hell do we explain this to normals?
			array('sendzid',		t('Extended Identity Sharing'),	t('Share your identity with all websites on the internet. When disabled, identity is only shared with sites in the matrix.')),
			array('expert',       t('Expert Mode'),                 t('Enable Expert Mode to provide advanced configuration options')),
			array('premium_channel', t('Premium Channel'), t('Allows you to set restrictions and terms on those that connect with your channel')),
		),

		// Post composition
		'composition' => array(
			t('Post Composition Features'),
			array('richtext',       t('Richtext Editor'),			t('Enable richtext editor')),
			array('preview',        t('Post Preview'),				t('Allow previewing posts and comments before publishing them')),
			array('channel_sources', t('Channel Sources'),          t('Automatically import channel content from other channels or feeds')),
			array('content_encrypt', t('Even More Encryption'),          t('Allow optional encryption of content end-to-end with a shared secret key')),
		),

		// Network Tools
		'net_module' => array(
			t('Network and Stream Filtering'),
			array('archives',       t('Search by Date'),			t('Ability to select posts by date ranges')),
			array('groups',    		t('Collections Filter'),		t('Enable widget to display Network posts only from selected collections')),
			array('savedsearch',    t('Saved Searches'),			t('Save search terms for re-use')),
			array('personal_tab',   t('Network Personal Tab'),		t('Enable tab to display only Network posts that you\'ve interacted on')),
			array('new_tab',   		t('Network New Tab'),			t('Enable tab to display all new Network activity')),
			array('affinity',       t('Affinity Tool'),			    t('Filter stream activity by depth of relationships')),
			array('suggest',    	t('Suggest Channels'),			t('Show channel suggestions')),
		),

		// Item tools
		'tools' => array(
			t('Post/Comment Tools'),
//			array('multi_delete',   t('Multiple Deletion'),			t('Select and delete multiple posts/comments at once')),
			array('edit_posts',     t('Edit Sent Posts'),			t('Edit and correct posts and comments after sending')),
			array('commtag',        t('Tagging'),					t('Ability to tag existing posts')),
			array('categories',     t('Post Categories'),			t('Add categories to your posts')),
			array('filing',         t('Saved Folders'),				t('Ability to file posts under folders')),
			array('dislike',        t('Dislike Posts'),				t('Ability to dislike posts/comments')),
			array('star_posts',     t('Star Posts'),				t('Ability to mark special posts with a star indicator')),
			array('tagadelic',      t('Tag Cloud'),				    t('Provide a personal tag cloud on your channel page')),
		),
	);

	call_hooks('get_features',$arr);
	return $arr;
}
