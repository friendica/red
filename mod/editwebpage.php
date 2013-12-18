<?php

// Required for setting permissions. (FIXME)

require_once('acl_selectors.php');

function editwebpage_content(&$a) {

	// We first need to figure out who owns the webpage, grab it from an argument

	$which = argv(1);

	// $a->get_channel() and stuff don't work here, so we've got to find the owner for ourselves.
	
	$r = q("select channel_id from channel where channel_address = '%s'",
		dbesc($which)
	);
	if($r) {
		$owner = intval($r[0]['channel_id']);
		//logger('owner: ' . print_r($owner,true));
	}

	$is_owner = ((local_user() && local_user() == $owner) ? true : false);
			
	$o = '';

	// Figure out which post we're editing
	$post_id = ((argc() > 2) ? intval(argv(2)) : 0);


	if(! $post_id) {
		notice( t('Item not found') . EOL);
		return;
	}

	// Now we've got a post and an owner, let's find out if we're allowed to edit it

	$observer = $a->get_observer();
	$ob_hash = (($observer) ? $observer['xchan_hash'] : '');

	$perms = get_all_perms($owner,$ob_hash);

	if(! $perms['write_pages']) {
		notice( t('Permission denied.') . EOL);
		return;
	}



	// We've already figured out which item we want and whose copy we need, so we don't need anything fancy here
	$itm = q("SELECT * FROM `item` WHERE `id` = %d and uid = %s LIMIT 1",
		intval($post_id),
		intval($owner)
   );


	if($itm[0]['item_flags'] & ITEM_OBSCURED) {
		$key = get_config('system','prvkey');
		if($itm[0]['title'])
			$itm[0]['title'] = crypto_unencapsulate(json_decode_plus($itm[0]['title']),$key);
		if($itm[0]['body'])
			$itm[0]['body'] = crypto_unencapsulate(json_decode_plus($itm[0]['body']),$key);
	}

	$item_id = q("select * from item_id where service = 'WEBPAGE' and iid = %d limit 1",
		$itm[0]['id']
	);
	if($item_id)
		$page_title = $item_id[0]['sid'];




	$plaintext = true;

	if(feature_enabled($itm[0]['uid'],'richtext'))
		$plaintext = false;

	$mimetype = $itm[0]['mimetype'];

	if($mimetype === 'application/x-php') {
		if((! local_user()) || (local_user() != $itm[0]['uid'])) {
			notice( t('Permission denied.') . EOL);
			return;
		}
	}
	
	$mimeselect = '';

	if($mimetype != 'text/bbcode')
		$plaintext = true;

	if(get_config('system','page_mimetype'))
	    $mimeselect = '<input type="hidden" name="mimetype" value="' . $mimetype . '" />';
	else
		$mimeselect = mimetype_select($itm[0]['uid'],$mimetype); 

	$layout = get_config('system','page_layout');
	if($layout)
		$layoutselect = '<input type="hidden" name="layout_mid" value="' . $layout . '" />'; 			
	else
		$layoutselect = layout_select($itm[0]['uid']);


	$o .= replace_macros(get_markup_template('edpost_head.tpl'), array(
		'$title' => t('Edit Webpage')
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
	
//FIXME A return path with $_SESSION doesn't always work for observer - it may WSoD instead of loading a sensible page.  So, send folk to the webpage list.

	$rp = '/webpages/' . $which;
	$lockstate = 

	$o .= replace_macros($tpl,array(
		'$return_path' => $rp,
		'$webpage' => true,
		'$placeholdpagetitle' => t('Page link title'),
		'$pagetitle' => $page_title,

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
		'$defloc' => $itm[0]['location'],
    	'$visitor' => ($is_owner) ? 'block' : 'none',
		'$acl' => populate_acl($itm[0]),
		'$showacl' => true,
		'$pvisit' => ($is_owner) ? 'block' : 'none',
		'$public' => t('Public post'),
		'$jotnets' => $jotnets,
		'$mimeselect' => $mimeselect,
		'$layoutselect' => $layoutselect,
		'$title' => htmlspecialchars($itm[0]['title'],ENT_COMPAT,'UTF-8'),
		'$placeholdertitle' => t('Set title'),
		'$category' => '',
		'$placeholdercategory' => t('Categories (comma-separated list)'),
		'$emtitle' => t('Example: bob@example.com, mary@example.com'),
		'lockstate' => (((strlen($itm[0]['allow_cid'])) || (strlen($itm[0]['allow_gid'])) || (strlen($itm[0]['deny_cid'])) || (strlen($itm[0]['deny_gid']))) ? 'lock' : 'unlock'),
		'$acl' => populate_acl($itm[0]), 
		'$bang' => '',
		'$profile_uid' => (intval($owner)),
		'$preview' => ((feature_enabled(local_user(),'preview')) ? t('Preview') : ''),
		'$jotplugins' => $jotplugins,
		'$sourceapp' => t($a->sourcename),
		'$defexpire' => '',
		'$feature_expire' => 'none',
		'$expires' => t('Set expiration date'),

	));

	$ob = get_observer_hash();

	if(($itm[0]['author_xchan'] === $ob) || ($itm[0]['owner_xchan'] === $ob))
		$o .= '<br /><br /><a href="item/drop/' . $itm[0]['id'] . '" >' . t('Delete Webpage') . '</a><br />';

	return $o;

}


