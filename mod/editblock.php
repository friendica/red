<?php

require_once('include/identity.php');
require_once('include/acl_selectors.php');

function editblock_init(&$a) {

	if(argc() > 1 && argv(1) === 'sys' && is_site_admin()) {
		$sys = get_sys_channel();
		if($sys && intval($sys['channel_id'])) {
			$a->is_sys = true;
		}
	}

	if(argc() > 1)
		$which = argv(1);
	else
		return;

	profile_load($a,$which);

}



function editblock_content(&$a) {

	if(! $a->profile) {
		notice( t('Requested profile is not available.') . EOL );
		$a->error = 404;
		return;
	}

	$which = argv(1);

	$uid = local_channel();
	$owner = 0;
	$channel = null;
	$observer = $a->get_observer();

	$channel = $a->get_channel();

	if($a->is_sys && is_site_admin()) {
		$sys = get_sys_channel();
		if($sys && intval($sys['channel_id'])) {
			$uid = $owner = intval($sys['channel_id']);
			$channel = $sys;
			$observer = $sys;
		}
	}

	if(! $owner) {
		// Figure out who the page owner is.
		$r = q("select channel_id from channel where channel_address = '%s'",
			dbesc($which)
		);
		if($r) {
			$owner = intval($r[0]['channel_id']);
		}
	}

	$ob_hash = (($observer) ? $observer['xchan_hash'] : '');

	if(! perm_is_allowed($owner,$ob_hash,'write_pages')) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$is_owner = (($uid && $uid == $owner) ? true : false);
			
	$o = '';


	// Figure out which post we're editing
	$post_id = ((argc() > 2) ? intval(argv(2)) : 0);


	if(! ($post_id && $owner)) {
		notice( t('Item not found') . EOL);
		return;
	}

	$itm = q("SELECT * FROM `item` WHERE `id` = %d and uid = %s LIMIT 1",
		intval($post_id),
		intval($owner)
	);
	if($itm) {
		$item_id = q("select * from item_id where service = 'BUILDBLOCK' and iid = %d limit 1",
			intval($itm[0]['id'])
		);
		if($item_id)
			$block_title = $item_id[0]['sid'];
	}
	else {
		notice( t('Item not found') . EOL);
		return;
	}


	$plaintext = true;

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
		'$baseurl'       => $a->get_baseurl(),
		'$editselect'    => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
		'$ispublic'      => '&nbsp;', // t('Visible to <strong>everybody</strong>'),
		'$geotag'        => '',
		'$nickname'      => $channel['channel_address'],
	    '$confirmdelete' => t('Delete block?')
	));


	$tpl = get_markup_template("jot.tpl");
		
	$jotplugins = '';
	$jotnets = '';

	call_hooks('jot_tool', $jotplugins);
	call_hooks('jot_networks', $jotnets);

	$rp = 'blocks/' . $channel['channel_address'];

	$o .= replace_macros($tpl,array(
		'$return_path'         => $rp,
		'$action'              => 'item',
		'$webpage'             => ITEM_BUILDBLOCK,
		'$share'               => t('Edit'),
		'$upload'              => t('Upload photo'),
		'$attach'              => t('Attach file'),
		'$weblink'             => t('Insert web link'),
		'$youtube'             => t('Insert YouTube video'),
		'$video'               => t('Insert Vorbis [.ogg] video'),
		'$audio'               => t('Insert Vorbis [.ogg] audio'),
		'$setloc'              => t('Set your location'),
		'$noloc'               => t('Clear browser location'),
		'$wait'                => t('Please wait'),
		'$permset'             => t('Permission settings'),
		'$ptyp'                => $itm[0]['type'],
		'$mimeselect'          => $mimeselect,
		'$content'             => undo_post_tagging($itm[0]['body']),
		'$post_id'             => $post_id,
		'$baseurl'             => $a->get_baseurl(),
		'$defloc'              => $channel['channel_location'],
		'$visitor'             => false,
		'$public'              => t('Public post'),
		'$jotnets'             => $jotnets,
		'$title'               => htmlspecialchars($itm[0]['title'],ENT_COMPAT,'UTF-8'),
		'$placeholdertitle'    => t('Title (optional)'),
		'$pagetitle'           => $block_title,
		'$category'            => '',
		'$placeholdercategory' => t('Categories (optional, comma-separated list)'),
		'$emtitle'             => t('Example: bob@example.com, mary@example.com'),
		'$lockstate'           => $lockstate,
		'$acl'                 => '', 
		'$bang'                => '',
		'$profile_uid'         => (intval($channel['channel_id'])),
		'$preview'             => true, // ((feature_enabled($uid,'preview')) ? t('Preview') : ''),
		'$jotplugins'          => $jotplugins,
		'$sourceapp'           => $itm[0]['app'],
		'$defexpire'           => '',
		'$feature_expire'      => false,
		'$expires'             => t('Set expiration date'),
	));


	if(($itm[0]['author_xchan'] === $ob_hash) || ($itm[0]['owner_xchan'] === $ob_hash))
		$o .= '<br /><br /><a class="block-delete-link" href="item/drop/' . $itm[0]['id'] . '" >' . t('Delete Block') . '</a><br />';


	$x = array(
		'type'      => 'block',
		'title'     => $itm[0]['title'],
		'body'      => $itm[0]['body'],
		'term'      => $itm[0]['term'],
		'created'   => $itm[0]['created'],
		'edited'    => $itm[0]['edited'],
		'mimetype'  => $itm[0]['mimetype'],
		'pagetitle' => $page_title,
		'mid'       => $itm[0]['mid']
	);

	$o .= EOL . EOL . t('Share') . EOL . '<textarea onclick="this.select();" class="shareable_element_text" >[element]' . base64url_encode(json_encode($x)) . '[/element]</textarea>' . EOL . EOL; 


	return $o;

}


