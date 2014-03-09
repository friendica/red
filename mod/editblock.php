<?php


function editblock_content(&$a) {


	if(argc() < 2) {
		notice( t('Item not found') . EOL);
		return;
	}

	$channel = get_channel_by_nick(argv(1));

	if($c) {
		$owner = intval($channel['channel_id']);
	}


	$o = '';


	// Figure out which post we're editing
	$post_id = ((argc() > 2) ? intval(argv(2)) : 0);


	if(! ($post_id && $channel)) {
		notice( t('Item not found') . EOL);
		return;
	}

	// Now we've got a post and an owner, let's find out if we're allowed to edit it

	if(! perm_is_allowed($channel['channel_id'],get_observer_hash(),'write_pages')) {
		notice( t('Permission denied.') . EOL);
		return;
	}



	// We've already figured out which item we want and whose copy we need, so we don't need anything fancy here
	$itm = q("SELECT * FROM `item` WHERE `id` = %d and uid = %s LIMIT 1",
		intval($post_id),
		intval($channel['channel_id'])
	);
	if($itm) {
		$item_id = q("select * from item_id where service = 'BUILDBLOCK' and iid = %d limit 1",
			$itm[0]['id']
		);
		if($item_id)
			$block_title = $item_id[0]['sid'];
	}
	else {
		notice( t('Item not found') . EOL);
		return;
	}


	$plaintext = true;

	// You may or may not be a local user.
	if(local_user() && feature_enabled(local_user(),'richtext'))
		$plaintext = false;

	$mimeselect = '';
	$mimetype = $itm[0]['mimetype'];

	if($mimetype != 'text/bbcode')
		$plaintext = true;

	if(get_config('system','page_mimetype'))
	    $mimeselect = '<input type="hidden" name="mimetype" value="' . $mimetype . '" />';
	else
		$mimeselect = mimetype_select($itm[0]['uid'],$mimetype); 


	$o .= replace_macros(get_markup_template('edpost_head.tpl'), array(
		'$title' => t('Edit Block')
	));

	
	$a->page['htmlhead'] .= replace_macros(get_markup_template('jot-header.tpl'), array(
		'$baseurl' => $a->get_baseurl(),
		'$editselect' =>  (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
		'$ispublic' => '&nbsp;', // t('Visible to <strong>everybody</strong>'),
		'$geotag' => '',
		'$nickname' => $channel['channel_address'],
	    '$confirmdelete' => t('Delete block?')
	));


	$tpl = get_markup_template("jot.tpl");
		
	$jotplugins = '';
	$jotnets = '';

	call_hooks('jot_tool', $jotplugins);
	call_hooks('jot_networks', $jotnets);


	//$tpl = replace_macros($tpl,array('$jotplugins' => $jotplugins));	
	
	// FIXME A return path with $_SESSION doesn't always work for observer - it may WSoD instead of loading a sensible page.  
	//So, send folk to the webpage list.

	$rp = 'blocks/' . $channel['channel_address'];

	$o .= replace_macros($tpl,array(
		'$return_path' => $rp,
		'$action' => 'item',
		'$webpage' => ITEM_BUILDBLOCK,
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
		'$mimeselect' => $mimeselect,
		'$content' => undo_post_tagging($itm[0]['body']),
		'$post_id' => $post_id,
		'$baseurl' => $a->get_baseurl(),
		'$defloc' => $channel['channel_location'],
		'$visitor' => 'none',
		'$pvisit' => 'none',
		'$public' => t('Public post'),
		'$jotnets' => $jotnets,
		'$title' => htmlspecialchars($itm[0]['title'],ENT_COMPAT,'UTF-8'),
		'$placeholdertitle' => t('Set title'),
		'$pagetitle' => $block_title,
		'$category' => '',
		'$placeholdercategory' => t('Categories (comma-separated list)'),
		'$emtitle' => t('Example: bob@example.com, mary@example.com'),
		'$lockstate' => $lockstate,
		'$acl' => '', 
		'$bang' => '',
		'$profile_uid' => (intval($channel['channel_id'])),
		'$preview' => ((feature_enabled(local_user(),'preview')) ? t('Preview') : ''),
		'$jotplugins' => $jotplugins,
		'$sourceapp' => $itm[0]['app'],
		'$defexpire' => '',
		'$feature_expire' => 'none',
		'$expires' => t('Set expiration date'),
	));


	$ob = get_observer_hash();

	if(($itm[0]['author_xchan'] === $ob) || ($itm[0]['owner_xchan'] === $ob))
		$o .= '<br /><br /><a class="block-delete-link" href="item/drop/' . $itm[0]['id'] . '" >' . t('Delete Block') . '</a><br />';

	return $o;

}


