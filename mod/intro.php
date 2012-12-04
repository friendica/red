<?php

function intro_post(&$a) {
	if(! local_user())
		return;
	if(! intval($_REQUEST['contact_id']))
		return;

	$flags = 0;
	if($_REQUEST['submit'] == t('Approve')) {
		;
	}
	elseif($_REQUEST['submit'] == t('Block')) {
		$flags = ABOOK_FLAG_BLOCKED;
	}
	elseif($_REQUEST['submit'] == t('Ignore')) {
		$flags = ABOOK_FLAG_IGNORED;
	}
	if(intval($_REQUEST['hidden']))
		$flags |= ABOOK_FLAG_HIDDEN;

	$r = q("update abook set abook_flags = %d where abook_channel = %d and abook_id = %d limit 1",
		intval($flags),
		intval(local_user()),
		intval($_REQUEST['contact_id'])
	);
	if($r)
		info( t('Connection updated.') . EOL);
	else
		notice( t('Connection update failed.') . EOL);

}



function intro_content(&$a) {

	if( ! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}


	$o = replace_macros(get_markup_template('intros_header.tpl'),array(
		'$title' => t('Introductions and Connection Requests')
	));

	$r = q("select count(abook_id) as total from abook where abook_channel = %d and (abook_flags & %d) and not (abook_flags & %d) ",
		intval(local_user()),
		intval(ABOOK_FLAG_PENDING),
		intval(ABOOK_FLAG_SELF)
	);
	if($r) {
		$a->set_pager_total($r[0]['total']);
		if(! intval($r[0]['total'])) {
			notice( t('No pending introductions.') . EOL);	
			return;
		}
	}
	else {
		notice( t('System error. Please try again later.') . EOL);
		return;
	}

	$r = q("select abook.*, xchan.* from abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d and (abook_flags & %d) and not (abook_flags & %d) LIMIT %d, %d",
		intval(local_user()),
		intval(ABOOK_FLAG_PENDING),
		intval(ABOOK_FLAG_SELF),
		intval($a->pager['start']), 
		intval($a->pager['itemspage'])
	);

	if($r) {

		$tpl = get_markup_template("intros.tpl");

			foreach($r as $rr) {
				$o .= replace_macros($tpl,array(
					'$uid' => local_user(),

					'$contact_id' => $rr['abook_id'],
					'$photo' => ((x($rr,'xchan_photo_l')) ? $rr['xchan_photo_l'] : "images/person-175.jpg"),
					'$fullname' => $rr['xchan_name'],
					'$hidden' => array('hidden', t('Hide this contact from others'), ($rr['abook_flags'] & ABOOK_FLAG_HIDDEN), ''),
					'$activity' => array('activity', t('Post a new friend activity'), (intval(get_pconfig(local_user(),'system','post_newfriend')) ? '1' : 0), t('if applicable')),
					'$url' => zid($rr['xchan_url']),
					'$approve' => t('Approve'),
					'$block' => t('Block'),
					'$ignore' => t('Ignore'),
					'$discard' => t('Discard')

				));
			}
		}

		$o .= paginate($a);
		return $o;

}