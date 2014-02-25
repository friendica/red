<?php

function achievements_content(&$a) {

	if(argc() > 1)
		$which = argv(1);
	else {
		notice( t('Requested profile is not available.') . EOL );
		return;
}

	$profile = 0;
	$profile = argv(1);		
	profile_load($a,$which,$profile);

	$r = q("select channel_id from channel where channel_address = '%s'",
			dbesc($which)
			);
	if($r) {
		$owner = intval($r[0]['channel_id']);
		}

	$observer = $a->get_observer();
	$ob_hash = (($observer) ? $observer['xchan_hash'] : '');
	$perms = get_all_perms($owner,$ob_hash);
	if(! $perms['view_profile']) {
		notice( t('Permission denied.') . EOL);
	return;
    }

//	By default, all badges are false
	$contactbadge = false;
	$profilebadge = false;
	$keywordsbadge = false;
	
// Check number of contacts.  Award a badge if over 10
// We'll figure these out on each page load instead of 
// writing them to the DB because that will mean one needs
// to retain their achievements - eg, you can't add
// a bunch of channels just to get your badge, and then
// delete them all again.  If these become popular or
// used in profiles or something, we may need to reconsider
// and add a table for this - because this won't scale.
    
    $r = q("select * from abook where abook_channel = %d",
	intval($owner)
	);

	if (count($r))
		$contacts = count($r);
	// We're checking for 11 to adjust for the abook record for self
	if ($contacts >= 11)
			$contactbadge = true;
		
//	Check if an about field in the profile has been created.

	$r = q("select * from profile where uid = %d and about <> ''",
			intval($owner)
	);
	
	if ($r)
		$profilebadge = 1;

// Check if keywords have been set

	$r = q("select * from profile where uid = %d and keywords <> ''",
			intval($owner)
	);
	
	if($r)
		$keywordsbadge = 1;

// FIXME - stick ths in a template, and make it look pretty.
	$o .= "Template not implemented";
	$o .= "If this is one, you get the profile badge" . $profilebadge . "<br>";
	$o .= "If this is one, you get the contact badge" . $contactbadge . "<br>";
	$o .= "If this is one you get the keywords badge" . $keywordsbadge . "<br>";
	$o .= "I haven't done the top level posts badge yet" . $toplevelpostsbadge . "<br>";
	$o .= "I haven't done the number of channels badge yet" . $channelsbadge;
	
	
return $o;

}
