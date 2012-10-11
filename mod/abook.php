<?php

require_once('include/Contact.php');
require_once('include/socgraph.php');
require_once('include/contact_selectors.php');

function abook_init(&$a) {
	if(! local_user())
		return;

	$contact_id = 0;

	if(($a->argc == 2) && intval($a->argv[1])) {
		$contact_id = intval($a->argv[1]);
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d and `id` = %d LIMIT 1",
			intval(local_user()),
			intval($contact_id)
		);
		if(! count($r)) {
			$contact_id = 0;
		}
	}

	require_once('include/group.php');
	require_once('include/contact_widgets.php');

	if(! x($a->page,'aside'))
		$a->page['aside'] = '';

	if($contact_id) {
			$a->data['contact'] = $r[0];
			$o .= '<div class="vcard">';
			$o .= '<div class="fn">' . $a->data['contact']['name'] . '</div>';
			$o .= '<div id="profile-photo-wrapper"><img class="photo" style="width: 175px; height: 175px;" src="' . $a->data['contact']['photo'] . '" alt="' . $a->data['contact']['name'] . '" /></div>';
			$o .= '</div>';
			$a->page['aside'] .= $o;

	}	
	else
		$a->page['aside'] .= follow_widget();

	$a->page['aside'] .= group_side('contacts','group',false,0,$contact_id);

	$a->page['aside'] .= findpeople_widget();

	$base = $a->get_baseurl();

	$a->page['htmlhead'] .= '<script src="' . $a->get_baseurl(true) . '/library/jquery_ac/friendica.complete.js" ></script>';
	$a->page['htmlhead'] .= <<< EOT

<script>$(document).ready(function() { 
	var a; 
	a = $("#contacts-search").autocomplete({ 
		serviceUrl: '$base/acl',
		minChars: 2,
		width: 350,
	});
	a.setOptions({ params: { type: 'a' }});

}); 

</script>
EOT;


}

function abook_post(&$a) {
	
	if(! local_user())
		return;

	$contact_id = intval($a->argv[1]);
	if(! $contact_id)
		return;

	$orig_record = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($contact_id),
		intval(local_user())
	);

	if(! count($orig_record)) {
		notice( t('Could not access contact record.') . EOL);
		goaway($a->get_baseurl(true) . '/contacts');
		return; // NOTREACHED
	}

	call_hooks('contact_edit_post', $_POST);

	$profile_id = intval($_POST['profile-assign']);
	if($profile_id) {
		$r = q("SELECT `id` FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($profile_id),
			intval(local_user())
		);
		if(! count($r)) {
			notice( t('Could not locate selected profile.') . EOL);
			return;
		}
	}

	$hidden = intval($_POST['hidden']);

	$priority = intval($_POST['poll']);
	if($priority > 5 || $priority < 0)
		$priority = 0;

	$closeness = intval($_POST['closeness']);
	if($closeness < 0)
		$closeness = 99;

	$info = fix_mce_lf(escape_tags(trim($_POST['info'])));

	$r = q("UPDATE `contact` SET `profile_id` = %d, `priority` = %d , `info` = '%s',
		`hidden` = %d, closeness = %d WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($profile_id),
		intval($priority),
		dbesc($info),
		intval($hidden),
		intval($closeness),
		intval($contact_id),
		intval(local_user())
	);
	if($r)
		info( t('Contact updated.') . EOL);
	else
		notice( t('Failed to update contact record.') . EOL);

	$r = q("select * from contact where id = %d and uid = %d limit 1",
		intval($contact_id),
		intval(local_user())
	);
	if($r && count($r))
		$a->data['contact'] = $r[0];

	return;

}



function abook_content(&$a) {

	$sort_type = 0;
	$o = '';
	nav_set_selected('abook');


	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if(argc() == 3) {


		$cmd = argv(1);
		if($cmd === 'profile') {
			$xchan_hash = argv(2);

			if($xchan_hash) {
				$r = q("select * from xchan where xchan_hash = '%s' limit 1",
					dbesc($xchan_hash)
				);
				if($r && count($r)) {
$o .= <<< EOT
<script language="JavaScript">
<!--
function resize_iframe()
{
	if(typeof(window.innerHeight) != 'undefined') {
		var height=window.innerHeight;//Firefox
	}
	else {
		if (typeof(document.body.clientHeight) != 'undefined')
		{
			var height=document.body.clientHeight;//IE
		}
	}

	//resize the iframe according to the size of the
	//window (all these should be on the same line)
	document.getElementById("glu").style.height=parseInt(height-document.getElementById("glu").offsetTop-8)+"px";
}

// this will resize the iframe every
// time you change the size of the window.
window.onresize=resize_iframe; 

//Instead of using this you can use: 
//	<BODY onresize="resize_iframe()">


//-->
</script>


<iframe id="glu" width="100%" src="{$r[0]['xchan_profile']}" onload="resize_iframe()">
</iframe>

EOT;


	//				$o .= '<div id="profile-frame-wrapper" style="width: 100%; height: 100%;"><iframe id="profile-frame" src="' . $r[0]['xchan_profile'] . '" style="width: 100%; height: 100%;"></iframe></div>';
					return $o;
				}
			}
		}

		$contact_id = intval(argv(1));
		if(! $contact_id)
			return;

		$cmd = argv(2);

		$orig_record = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d AND `self` = 0 LIMIT 1",
			intval($contact_id),
			intval(local_user())
		);

		if(! count($orig_record)) {
			notice( t('Could not access contact record.') . EOL);
			goaway($a->get_baseurl(true) . '/contacts');
			return; // NOTREACHED
		}
		
		if($cmd === 'update') {

			// pull feed and consume it, which should subscribe to the hub.
			proc_run('php',"include/poller.php","$contact_id");
			goaway($a->get_baseurl(true) . '/contacts/' . $contact_id);
			// NOTREACHED
		}

		if($cmd === 'block') {
			$blocked = (($orig_record[0]['blocked']) ? 0 : 1);
			$r = q("UPDATE `contact` SET `blocked` = %d WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($blocked),
				intval($contact_id),
				intval(local_user())
			);
			if($r) {
				//notice( t('Contact has been ') . (($blocked) ? t('blocked') : t('unblocked')) . EOL );
				info( (($blocked) ? t('Contact has been blocked') : t('Contact has been unblocked')) . EOL );
			}
			goaway($a->get_baseurl(true) . '/contacts/' . $contact_id);
			return; // NOTREACHED
		}

		if($cmd === 'ignore') {
			$readonly = (($orig_record[0]['readonly']) ? 0 : 1);
			$r = q("UPDATE `contact` SET `readonly` = %d WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($readonly),
				intval($contact_id),
				intval(local_user())
			);
			if($r) {
				info( (($readonly) ? t('Contact has been ignored') : t('Contact has been unignored')) . EOL );
			}
			goaway($a->get_baseurl(true) . '/contacts/' . $contact_id);
			return; // NOTREACHED
		}


		if($cmd === 'archive') {
			$archived = (($orig_record[0]['archive']) ? 0 : 1);
			$r = q("UPDATE `contact` SET `archive` = %d WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($archived),
				intval($contact_id),
				intval(local_user())
			);
			if($r) {
				//notice( t('Contact has been ') . (($archived) ? t('archived') : t('unarchived')) . EOL );
				info( (($archived) ? t('Contact has been archived') : t('Contact has been unarchived')) . EOL );
			}
			goaway($a->get_baseurl(true) . '/contacts/' . $contact_id);
			return; // NOTREACHED
		}

		if($cmd === 'drop') {

			require_once('include/Contact.php');

			terminate_friendship($a->user,$a->contact,$orig_record[0]);

			contact_remove($orig_record[0]['id']);
			info( t('Contact has been removed.') . EOL );
			if(x($_SESSION,'return_url'))
				goaway($a->get_baseurl(true) . '/' . $_SESSION['return_url']);
			else
				goaway($a->get_baseurl(true) . '/contacts');
			return; // NOTREACHED
		}
	}

	if((x($a->data,'contact')) && (is_array($a->data['contact']))) {

		$contact_id = $a->data['contact']['id'];
		$contact = $a->data['contact'];

		$editselect = 'exact';
		if(intval(get_pconfig(local_user(),'system','plaintext')))
			$editselect = 'none';

		$a->page['htmlhead'] .= replace_macros(get_markup_template('contact_head.tpl'), array(
			'$baseurl' => $a->get_baseurl(true),
			'$editselect' => $editselect,
		));

		require_once('include/contact_selectors.php');

		$tpl = get_markup_template("abook_edit.tpl");

		$slider_tpl = get_markup_template('contact_slider.tpl');
		$slide = replace_macros($slider_tpl,array(
			'$me' => t('Me'),
			'$val' => $contact['closeness'],
			'$intimate' => t('Best Friends'),
			'$friends' => t('Friends'),
			'$coworkers' => t('Co-workers'),
			'$oldfriends' => t('Former Friends'),
			'$acquaintances' => t('Acquaintances'),
			'$world' => t('Unknown')
		));

		$o .= replace_macros($tpl,array(

			'$header' => t('Contact Settings') . ' for ' . $contact['name'],
			'$slide' => $slide,
			'$tab_str' => $tab_str,
			'$submit' => t('Submit'),
			'$lbl_vis1' => t('Profile Visibility'),
			'$lbl_vis2' => sprintf( t('Please choose the profile you would like to display to %s when viewing your profile securely.'), $contact['name']),
			'$lbl_info1' => t('Contact Information / Notes'),
			'$infedit' => t('Edit contact notes'),
			'$close' => $contact['closeness'],
			'$them' => t('Their Settings'),
			'$me' => t('My Settings'),

			'$perm01' => array( 'perm01', t('Can be seen in my address book')),
			'$perm02' => array( 'perm02', t('Can post to my stream')),
			'$perm03' => array( 'perm03', t('Can see my posts')),
			'$perm04' => array( 'perm04', t('Can comment on my posts')),
			'$perm05' => array( 'perm05', t('Can post to my wall'), false, t('if I allow wall posts')),
			'$perm06' => array( 'perm06', t('Can post to my wall via tags'), false, t('e.g. public groups')),
			'$perm07' => array( 'perm07', t('Can send me email')),
			'$perm08' => array( 'perm08', t('Can see my address book'), false, t('if it is not public')),
			'$perm09' => array( 'perm09', t('Can IM me'), false, t('when available')),
			'$perm10' => array( 'perm10', t('Can see these permissions')),



			'$common_link' => $a->get_baseurl(true) . '/common/loc/' . local_user() . '/' . $contact['id'],
			'$all_friends' => $all_friends,
			'$relation_text' => $relation_text,
			'$visit' => sprintf( t('Visit %s\'s profile [%s]'),$contact['name'],$contact['url']),
			'$blockunblock' => t('Block/Unblock contact'),
			'$ignorecont' => t('Ignore contact'),
			'$lblcrepair' => t("Repair URL settings"),
			'$lblrecent' => t('View conversations'),
			'$lblsuggest' => $lblsuggest,
			'$delete' => t('Delete contact'),
			'$nettype' => $nettype,
			'$poll_interval' => contact_poll_interval($contact['priority'],(! $poll_enabled)),
			'$poll_enabled' => $poll_enabled,
			'$lastupdtext' => t('Last update:'),
			'$lost_contact' => $lost_contact,
			'$updpub' => t('Update public posts'),
			'$last_update' => $last_update,
			'$udnow' => t('Update now'),
			'$profile_select' => contact_profile_assign($contact['profile_id'],(($contact['network'] !== NETWORK_DFRN) ? true : false)),
			'$contact_id' => $contact['id'],
			'$block_text' => (($contact['blocked']) ? t('Unblock') : t('Block') ),
			'$ignore_text' => (($contact['readonly']) ? t('Unignore') : t('Ignore') ),
			'$insecure' => (($contact['network'] !== NETWORK_DFRN && $contact['network'] !== NETWORK_MAIL && $contact['network'] !== NETWORK_FACEBOOK && $contact['network'] !== NETWORK_DIASPORA) ? $insecure : ''),
			'$info' => $contact['info'],
			'$blocked' => (($contact['blocked']) ? t('Currently blocked') : ''),
			'$ignored' => (($contact['readonly']) ? t('Currently ignored') : ''),
			'$archived' => (($contact['archive']) ? t('Currently archived') : ''),
			'$hidden' => array('hidden', t('Hide this contact from others'), ($contact['hidden'] == 1), t('Replies/likes to your public posts <strong>may</strong> still be visible')),
			'$photo' => $contact['photo'],
			'$name' => $contact['name'],
			'$dir_icon' => $dir_icon,
			'$alt_text' => $alt_text,
			'$sparkle' => $sparkle,
			'$url' => $url

		));

		$arr = array('contact' => $contact,'output' => $o);

		call_hooks('contact_edit', $arr);

		return $arr['output'];

	}

	$blocked = false;
	$hidden = false;
	$ignored = false;
	$all = false;

	$_SESSION['return_url'] = $a->query_string;

	if(($a->argc == 2) && ($a->argv[1] === 'all')) {
		$sql_extra = '';
		$all = true;
	}
	elseif(($a->argc == 2) && ($a->argv[1] === 'blocked')) {
		$sql_extra = " AND `blocked` = 1 ";
		$blocked = true;
	}
	elseif(($a->argc == 2) && ($a->argv[1] === 'hidden')) {
		$sql_extra = " AND `hidden` = 1 ";
		$hidden = true;
	}
	elseif(($a->argc == 2) && ($a->argv[1] === 'ignored')) {
		$sql_extra = " AND `readonly` = 1 ";
		$ignored = true;
	}
	elseif(($a->argc == 2) && ($a->argv[1] === 'archived')) {
		$sql_extra = " AND `archive` = 1 ";
		$archived = true;
	}
	else
		$sql_extra = " AND `blocked` = 0 ";

	$search = ((x($_GET,'search')) ? notags(trim($_GET['search'])) : '');
	$nets = ((x($_GET,'nets')) ? notags(trim($_GET['nets'])) : '');

	$tabs = array(
		array(
			'label' => t('Suggestions'),
			'url'   => $a->get_baseurl(true) . '/suggest', 
			'sel'   => '',
			'title' => t('Suggest potential friends'),
		),
		array(
			'label' => t('All Contacts'),
			'url'   => $a->get_baseurl(true) . '/contacts/all', 
			'sel'   => ($all) ? 'active' : '',
			'title' => t('Show all contacts'),
		),
		array(
			'label' => t('Unblocked'),
			'url'   => $a->get_baseurl(true) . '/contacts',
			'sel'   => ((! $all) && (! $blocked) && (! $hidden) && (! $search) && (! $nets) && (! $ignored) && (! $archived)) ? 'active' : '',
			'title' => t('Only show unblocked contacts'),
		),

		array(
			'label' => t('Blocked'),
			'url'   => $a->get_baseurl(true) . '/contacts/blocked',
			'sel'   => ($blocked) ? 'active' : '',
			'title' => t('Only show blocked contacts'),
		),

		array(
			'label' => t('Ignored'),
			'url'   => $a->get_baseurl(true) . '/contacts/ignored',
			'sel'   => ($ignored) ? 'active' : '',
			'title' => t('Only show ignored contacts'),
		),

		array(
			'label' => t('Archived'),
			'url'   => $a->get_baseurl(true) . '/contacts/archived',
			'sel'   => ($archived) ? 'active' : '',
			'title' => t('Only show archived contacts'),
		),

		array(
			'label' => t('Hidden'),
			'url'   => $a->get_baseurl(true) . '/contacts/hidden',
			'sel'   => ($hidden) ? 'active' : '',
			'title' => t('Only show hidden contacts'),
		),

	);

	$tab_tpl = get_markup_template('common_tabs.tpl');
	$t = replace_macros($tab_tpl, array('$tabs'=>$tabs));



	$searching = false;
	if($search) {
		$search_hdr = $search;
		$search_txt = dbesc(protect_sprintf(preg_quote($search)));
		$searching = true;
	}
	$sql_extra .= (($searching) ? " AND `name` REGEXP '$search_txt' " : "");

	if($nets)
		$sql_extra .= sprintf(" AND network = '%s' ", dbesc($nets));
 
	$sql_extra2 = ((($sort_type > 0) && ($sort_type <= CONTACT_IS_FRIEND)) ? sprintf(" AND `rel` = %d ",intval($sort_type)) : ''); 

	
	$r = q("SELECT COUNT(*) AS `total` FROM `contact` 
		WHERE `uid` = %d AND `self` = 0 AND `pending` = 0 $sql_extra $sql_extra2 ",
		intval($_SESSION['uid']));
	if(count($r)) {
		$a->set_pager_total($r[0]['total']);
		$total = $r[0]['total'];
	}


	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `pending` = 0 $sql_extra $sql_extra2 ORDER BY `name` ASC LIMIT %d , %d ",
		intval($_SESSION['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	$contacts = array();

	if(count($r)) {

		foreach($r as $rr) {

			switch($rr['rel']) {
				case CONTACT_IS_FRIEND:
					$dir_icon = 'images/lrarrow.gif';
					$alt_text = t('Mutual Friendship');
					break;
				case  CONTACT_IS_FOLLOWER;
					$dir_icon = 'images/larrow.gif';
					$alt_text = t('is a fan of yours');
					break;
				case CONTACT_IS_SHARING;
					$dir_icon = 'images/rarrow.gif';
					$alt_text = t('you are a fan of');
					break;
				default:
					break;
			}
			if(($rr['network'] === 'dfrn') && ($rr['rel'])) {
				$url = "redir/{$rr['id']}";
				$sparkle = ' class="sparkle" ';
			}
			else { 
				$url = $rr['url'];
				$sparkle = '';
			}


			$contacts[] = array(
				'img_hover' => sprintf( t('Visit %s\'s profile [%s]'),$rr['name'],$rr['url']),
				'edit_hover' => t('Edit contact'),
				'photo_menu' => contact_photo_menu($rr),
				'id' => $rr['id'],
				'alt_text' => $alt_text,
				'dir_icon' => $dir_icon,
				'thumb' => $rr['thumb'], 
				'name' => $rr['name'],
				'username' => $rr['name'],
				'sparkle' => $sparkle,
				'itemurl' => $rr['url'],
				'url' => $url,
				'network' => network_to_name($rr['network']),
			);
		}

		

	}
	
	$tpl = get_markup_template("contacts-template.tpl");
	$o .= replace_macros($tpl,array(
		'$header' => t('Contacts') . (($nets) ? ' - ' . network_to_name($nets) : ''),
		'$tabs' => $t,
		'$total' => $total,
		'$search' => $search_hdr,
		'$desc' => t('Search your contacts'),
		'$finding' => (($searching) ? t('Finding: ') . "'" . $search . "'" : ""),
		'$submit' => t('Find'),
		'$cmd' => $a->cmd,
		'$contacts' => $contacts,
		'$paginate' => paginate($a),

	)); 
	
	return $o;
}
