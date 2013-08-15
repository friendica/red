 
<?php

// What is this here for?  I think it's cruft, but comment out for now in case it's here for a reason
// require_once('acl_selectors.php');

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


		
		
        if((local_user()) && (argc() > 2) && (argv(2) === 'view')) {
                $which = $channel['channel_address'];
        }


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



	$plaintext = true;
// You may or may not be a local user.  This won't work,
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
	
//FIXME A return path with $_SESSION doesn't always work for observer - it may WSoD instead of loading a sensible page.  So, send folk to the webpage list.

	$rp = '/webpages/' . $which;

	$o .= replace_macros($tpl,array(
		'$return_path' => $rp,
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
		'$profile_uid' => (intval($owner)),
		'$preview' => ((feature_enabled(local_user(),'preview')) ? t('Preview') : ''),
		'$jotplugins' => $jotplugins,
		'$sourceapp' => t($a->sourcename),
	));

	return $o;

}


