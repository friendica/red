<?php

require_once('acl_selectors.php');
require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/taxonomy.php');

function editpost_content(&$a) {

	$o = '';

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$post_id = ((argc() > 1) ? intval(argv(1)) : 0);

	if(! $post_id) {
		notice( t('Item not found') . EOL);
		return;
	}

	$itm = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d and author_xchan = '%s' LIMIT 1",
		intval($post_id),
		intval(local_user()),
		dbesc(get_observer_hash())
	);

	if(! count($itm)) {
		notice( t('Item is not editable') . EOL);
		return;
	}

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



	if($itm[0]['item_flags'] & ITEM_OBSCURED) {
		$key = get_config('system','prvkey');
		if($itm[0]['title'])
			$itm[0]['title'] = aes_unencapsulate(json_decode_plus($itm[0]['title']),$key);
		if($itm[0]['body'])
			$itm[0]['body'] = aes_unencapsulate(json_decode_plus($itm[0]['body']),$key);
	}

	$tpl = get_markup_template("jot.tpl");
		
	$jotplugins = '';
	$jotnets = '';

	call_hooks('jot_tool', $jotplugins);
	call_hooks('jot_networks', $jotnets);

	$channel = $a->get_channel();

	//$tpl = replace_macros($tpl,array('$jotplugins' => $jotplugins));	
	

	$category = '';
	$catsenabled = ((feature_enabled(local_user(),'categories')) ? 'categories' : '');

	if ($catsenabled){
	        $itm = fetch_post_tags($itm);

                $cats = get_terms_oftype($itm[0]['term'], TERM_CATEGORY);

	        foreach ($cats as $cat) {
	                if (strlen($category))
	                        $category .= ', ';
	                $category .= $cat['term'];
	        }

	}

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
		'$category' => $category,
		'$placeholdercategory' => t('Categories (comma-separated list)'),
		'$emtitle' => t('Example: bob@example.com, mary@example.com'),
		'$lockstate' => $lockstate,
		'$acl' => '', 
		'$bang' => '',
		'$profile_uid' => local_user(),
		'$preview' => ((feature_enabled(local_user(),'preview')) ? t('Preview') : ''),
		'$jotplugins' => $jotplugins,
		'$sourceapp' => t($a->sourcename),
		'$catsenabled' => $catsenabled,
	));

	return $o;

}


