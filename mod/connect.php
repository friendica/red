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
			$r = q("update channel set channel_flags = channel_flags ^ %d where channel_id = %d limit 1",
				intval(PAGE_PREMIUM),
				intval(local_user()) 
			);
		set_pconfig($a->profile['profile_uid'],'system','selltext',$text);
		return;
	}

	$url = '';
	$observer = $a->get_observer();
	if(($observer) && ($_POST['submit'] === t('Continue'))) {
		if($observer['xchan_follow'])
			$url = sprintf($observer['xchan_follow'],urlencode($a->profile['channel_address'] . '@' . $a->get_hostname())); 
		if(! $url) {
			$r = q("select * from hubloc where hubloc_hash = '%s' order by hubloc_id desc limit 1",
				dbesc($observer['xchan_hash'])
			);
			if($r)
				$url = $r[0]['hubloc_url'] . '/follow?f=&url=' . urlencode($a->profile['channel_address'] . '@' . $a->get_hostname()); 
		}
	}
	if($url)
		goaway($url);
	else
		notice('Unable to connect to your home hub location.');

}



function connect_content(&$a) {

	$edit = ((local_user() && (local_user() == $a->profile['profile_uid'])) ? true : false);

	$text = get_pconfig($a->profile['profile_uid'],'system','selltext');

	if($edit) {
		$o = replace_macros(get_markup_template('sellpage_edit.tpl'),array(


		));
		return $o;
	}
	else {
		$submit = replace_macros(get_markup_template('sellpage_submit.tpl'), array(
			'$continue' => t('Continue'),			
			'$address' => $a->profile['channel_address']
		));

		$o = replace_macros(get_markup_template('sellpage_view.tpl'),array(
			'$header' => t('Restricted Channel'),
			'$desc' => t('This channel may require additional steps or acknowledgement of the following conditions prior to connecting:'),
			'$text' => prepare_text($text), 

			'$desc2' => t('By continuing, I certify that I have complied with any instructions provided on this page.'),
			'$submit' => $submit,

		));




	}

	return $o;
}