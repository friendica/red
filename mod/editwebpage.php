 
<?php

require_once('acl_selectors.php');

function editwebpage_content(&$a) {

	$o = '';

	// We can do better, but for now, editing only works for your own pages, so...
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$post_id = ((argc() > 1) ? intval(argv(1)) : 0);

	if(! $post_id) {
		notice( t('Item not found') . EOL);
		return;
	}

	// uid and author_xchan alone should be enough - but it doesn't seem to be any more expensive to use both, so keep it in case of edge cases
        $itm = q("SELECT * FROM `item` WHERE `id` = %d and uid = %s and author_xchan = '%s' LIMIT 1",
                intval($post_id),
                intval(local_user()),
                dbesc(get_observer_hash())
        );



	// All of the following is straight from editpost - but we'll need richer editing options for webpages eventually, so we may as well have it's own mod now.

	$plaintext = true;
	if(feature_enabled(local_user(),'richtext'))
		$plaintext = false;

	$o .= replace_macros(get_markup_template('edpost_head.tpl'), array(
		'$title' => t('Edit post')
	));

	
	$a->page['htmlhead'] .= replace_macros(get_markup_template('jot-header.tpl'), array(
		'$baseurl' => $a->get_baseurl(),
		'$editselect' =>  (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
		'$ispublic' => '&nbsp;', // t('Visible to <strong>everybody</strong>'),
		'$geotag' => $geotag,
		'$nickname' => $a->user['nickname']
	));


	$tpl = get_markup_template("jot.tpl");
		
	$jotplugins = '';
	$jotnets = '';

	call_hooks('jot_tool', $jotplugins);
	call_hooks('jot_networks', $jotnets);

	$channel = $a->get_channel();

	//$tpl = replace_macros($tpl,array('$jotplugins' => $jotplugins));	
	

	$o .= replace_macros($tpl,array(
		'$return_path' => $_SESSION['return_url'],
		'$action' => 'item',
		'$share' => t('Edit'),
		'$upload' => t('Upload photo'),
		'$attach' => t('Attach file'),
		'$weblink' => t('Insert web link'),
		'$youtube' => t('Insert YouTube video'),
		'$video' => t('Insert Vorbis [.ogg] video'),
		'$audio' => t('Insert Vorbis [.ogg] audio'),
		'$setloc' => t('Set your location'),
		'$noloc' => t('Clear browser location'),
		'$wait' => t('Please wait'),
		'$permset' => t('Permission settings'),
		'$ptyp' => $itm[0]['type'],
		'$content' => undo_post_tagging($itm[0]['body']),
		'$post_id' => $post_id,
		'$baseurl' => $a->get_baseurl(),
		'$defloc' => $channel['channel_location'],
		'$visitor' => 'none',
		'$pvisit' => 'none',
		'$public' => t('Public post'),
		'$jotnets' => $jotnets,
		'$title' => htmlspecialchars($itm[0]['title']),
		'$placeholdertitle' => t('Set title'),
		'$category' => '',
		'$placeholdercategory' => t('Categories (comma-separated list)'),
		'$emtitle' => t('Example: bob@example.com, mary@example.com'),
		'$lockstate' => $lockstate,
		'$acl' => '', 
		'$bang' => '',
		'$profile_uid' => local_user(),
		'$preview' => ((feature_enabled(local_user(),'preview')) ? t('Preview') : ''),
		'$jotplugins' => $jotplugins,
		'$sourceapp' => t($a->sourcename),
	));

	return $o;

}


