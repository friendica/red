<?php /** @file */

/*
 * Features management
 */


function feature_enabled($uid,$feature) {
	$x = get_pconfig($uid,'feature',$feature);
	if($x === false) {
		$x = get_config('feature',$feature);
		if($x === false)
			$x = get_feature_default($feature);
	}
	$arr = array('uid' => $uid, 'feature' => $feature, 'enabled' => $x);
	call_hooks('feature_enabled',$arr);
	return($arr['enabled']);
}

function get_feature_default($feature) {
	$f = get_features();
	foreach($f as $cat) {
		foreach($cat as $feat) {
			if(is_array($feat) && $feat[0] === $feature)
				return $feat[3];
		}
	}
	return false;
}


function get_features() {

	$arr = array(

		// General
		'general' => array(
			t('General Features'),
// This is per post, and different from fixed expiration 'expire' which isn't working yet
			array('content_expire',      t('Content Expiration'),     t('Remove posts/comments and/or private messages at a future time'), false),
			array('multi_profiles',      t('Multiple Profiles'),      t('Ability to create multiple profiles'), false),
			array('advanced_profiles',   t('Advanced Profiles'),      t('Additional profile sections and selections'),false),
			array('profile_export',      t('Profile Import/Export'),  t('Save and load profile details across sites/channels'),false),
			array('webpages',            t('Web Pages'),              t('Provide managed web pages on your channel'),false),
			array('private_notes',       t('Private Notes'),          t('Enables a tool to store notes and reminders'),false),
			array('nav_channel_select',  t('Navigation Channel Select'), t('Change channels directly from within the navigation dropdown menu'),false),


			//FIXME - needs a description, but how the hell do we explain this to normals?
			array('sendzid',		t('Extended Identity Sharing'),	t('Share your identity with all websites on the internet. When disabled, identity is only shared with sites in the matrix.'),false),
			array('expert',       t('Expert Mode'),                 t('Enable Expert Mode to provide advanced configuration options'),false),
			array('premium_channel', t('Premium Channel'), t('Allows you to set restrictions and terms on those that connect with your channel'),false),
		),

		// Post composition
		'composition' => array(
			t('Post Composition Features'),
//			array('richtext',       t('Richtext Editor'),			t('Enable richtext editor'),false),
			array('markdown',       t('Use Markdown'),              t('Allow use of "Markdown" to format posts'),false),
			array('large_photos',   t('Large Photos'),              t('Include large (640px) photo thumbnails in posts. If not enabled, use small (320px) photo thumbnails'),false),
			array('channel_sources', t('Channel Sources'),          t('Automatically import channel content from other channels or feeds'),false),
			array('content_encrypt', t('Even More Encryption'),          t('Allow optional encryption of content end-to-end with a shared secret key'),false),
			array('adult_photo_flagging', t('Flag Adult Photos'),   t('Provide photo edit option to hide adult photos from default album view'),false), 
		),

		// Network Tools
		'net_module' => array(
			t('Network and Stream Filtering'),
			array('archives',       t('Search by Date'),			t('Ability to select posts by date ranges'),false),
			array('groups',    		t('Collections Filter'),		t('Enable widget to display Network posts only from selected collections'),false),
			array('savedsearch',    t('Saved Searches'),			t('Save search terms for re-use'),false),
			array('personal_tab',   t('Network Personal Tab'),		t('Enable tab to display only Network posts that you\'ve interacted on'),false),
			array('new_tab',   		t('Network New Tab'),			t('Enable tab to display all new Network activity'),false),
			array('affinity',       t('Affinity Tool'),			    t('Filter stream activity by depth of relationships'),false),
			array('suggest',    	t('Suggest Channels'),			t('Show channel suggestions'),false),
		),

		// Item tools
		'tools' => array(
			t('Post/Comment Tools'),
			array('commtag',        t('Tagging'),					t('Ability to tag existing posts'),false),
			array('categories',     t('Post Categories'),			t('Add categories to your posts'),false),
			array('filing',         t('Saved Folders'),				t('Ability to file posts under folders'),false),
			array('dislike',        t('Dislike Posts'),				t('Ability to dislike posts/comments'),false),
			array('star_posts',     t('Star Posts'),				t('Ability to mark special posts with a star indicator'),false),
			array('tagadelic',      t('Tag Cloud'),				    t('Provide a personal tag cloud on your channel page'),false),
		),
	);

	call_hooks('get_features',$arr);
	return $arr;
}
