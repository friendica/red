<?php /** @file */


require_once('include/Contact.php');
require_once('include/contact_widgets.php');
require_once('include/items.php');


function connect_init(&$a) {
	if(argc() > 1)
		$which = argv(1);
	else {
		notice( t('Requested profile is not available.') . EOL );
		$a->error = 404;
		return;
	}

	$r = q("select * from channel where channel_address = '%s' limit 1",
		dbesc($which)
	);

	if($r)
		$a->data['channel'] = $r[0];

	profile_load($a,$which,'');

    profile_create_sidebar($a,false);


}

function connect_post(&$a) {

	$edit = ((local_user() && (local_user() == $a->profile['profile_uid'])) ? true : false);

	if($edit) {
		$premium = (($_POST['premium']) ? intval($_POST['premium']) : 0);
		$text = escape_tags($_POST['text']);
		
		$channel = $a->get_channel();
		if(($channel['channel_pageflags'] & PAGE_PREMIUM) != $premium)
			$r = q("update channel set channel_pageflags = channel_pageflags ^ %d where channel_id = %d limit 1",
				intval(PAGE_PREMIUM),
				intval(local_user()) 
			);
		set_pconfig($a->profile['profile_uid'],'system','selltext',$text);
		goaway(z_root() . '/' . $a->query_string);

	}

	$url = '';
	$observer = $a->get_observer();
	if(($observer) && ($_POST['submit'] === t('Continue'))) {
		if($observer['xchan_follow'])
			$url = sprintf($observer['xchan_follow'],urlencode($a->data['channel']['channel_address'] . '@' . $a->get_hostname())); 
		if(! $url) {
			$r = q("select * from hubloc where hubloc_hash = '%s' order by hubloc_id desc limit 1",
				dbesc($observer['xchan_hash'])
			);
			if($r)
				$url = $r[0]['hubloc_url'] . '/follow?f=&url=' . urlencode($a->data['channel']['channel_address'] . '@' . $a->get_hostname()); 
		}
	}
	if($url)
		goaway($url . '&confirm=1');
	else
		notice('Unable to connect to your home hub location.');

}



function connect_content(&$a) {

	$edit = ((local_user() && (local_user() == $a->data['channel']['channel_id'])) ? true : false);

	$text = get_pconfig($a->data['channel']['channel_id'],'system','selltext');

	if($edit) {

		$o = replace_macros(get_markup_template('sellpage_edit.tpl'),array(
			'$header' => t('Premium Channel Setup'),
			'$address' => $a->data['channel']['channel_address'],
			'$premium' => array('premium', t('Enable premium channel connection restrictions'),(($a->data['channel']['channel_pageflags'] & PAGE_PREMIUM) ? '1' : ''),''),
			'$lbl_about' => t('Please enter your restrictions or conditions, such as paypal receipt, usage guidelines, etc.'),
 			'$text' => $text,
			'$desc' => t('This channel may require additional steps or acknowledgement of the following conditions prior to connecting:'),
			'$lbl2' => t('Potential connections will then see the following text before proceeding:'),
			'$desc2' => t('By continuing, I certify that I have complied with any instructions provided on this page.'),
			'$submit' => t('Submit'),


		));
		return $o;
	}
	else {
		if(! $text)
			$text = t('(No specific instructions have been provided by the channel owner.)');

		$submit = replace_macros(get_markup_template('sellpage_submit.tpl'), array(
			'$continue' => t('Continue'),			
			'$address' => $a->data['channel']['channel_address']
		));

		$o = replace_macros(get_markup_template('sellpage_view.tpl'),array(
			'$header' => t('Restricted or Premium Channel'),
			'$desc' => t('This channel may require additional steps or acknowledgement of the following conditions prior to connecting:'),
			'$text' => prepare_text($text), 

			'$desc2' => t('By continuing, I certify that I have complied with any instructions provided on this page.'),
			'$submit' => $submit,

		));

		$arr = array('channel' => $a->data['channel'],'observer' => $a->get_observer(), 'sellpage' => $o, 'submit' => $submit);
		call_hooks('connect_premium', $arr);
		$o = $arr['sellpage'];

	}

	return $o;
}