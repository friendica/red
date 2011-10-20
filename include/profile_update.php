<?php

require_once('include/datetime.php');
require_once('include/diaspora.php');

function profile_change() {

	$a = get_app();
	
	if(! local_user())
		return;

//   $url = $a->get_baseurl() . '/profile/' . $a->user['nickname'];
 //   if($url && strlen(get_config('system','directory_submit_url')))
  //      proc_run('php',"include/directory.php","$url");

	$recips = q("SELECT DISTINCT(`batch`), `id`, `name`,`network` FROM `contact` WHERE `network` = '%s'
		AND `uid` = %d AND `rel` != %d ORDER BY rand() ",
		dbesc(NETWORK_DIASPORA),
		intval(local_user()),
		intval(CONTACT_IS_SHARING)
	);
	if(! count($recips))
		return;

	$r = q("SELECT `profile`.`uid` AS `profile_uid`, `profile`.* , `user`.* FROM `profile`
		LEFT JOIN `user` ON `profile`.`uid` = `user`.`uid`
		WHERE `user`.`uid` = %d AND `profile`.`is-default` = 1 LIMIT 1",
		intval(local_user())
	);
	
	if(! count($r))
		return;
	$profile = $r[0];

	$handle = $a->user['nickname'] . '@' . substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);
	$first = ((strpos($profile['name'],' '))
		? trim(substr($profile['name'],0,strpos($profile['name'],' '))) : $profile['name']);
	$last = (($first === $profile['name']) ? '' : trim(substr($profile['name'],strlen($first))));
	$large = $a->get_baseurl() . '/photo/custom/300/' . $profile['uid'] . '.jpg';
	$medium = $a->get_baseurl() . '/photo/custom/100/' . $profile['uid'] . '.jpg';
	$small = $a->get_baseurl() . '/photo/custom/50/'  . $profile['uid'] . '.jpg';
	$searchable = (($profile['publish'] && $profile['net-publish']) ? 'true' : 'false' );

	if($searchable === 'true') {
		$dob = '1000-00-00';

		if(($profile['dob']) && ($profile['dob'] != '0000-00-00'))
			$dob = ((intval($profile['dob'])) ? intval($profile['dob']) : '1000') . '-' . datetime_convert('UTC','UTC',$profile['dob'],'m-d');
		$gender = $profile['gender'];
		$about = $profile['about'];
		require_once('include/bbcode.php');
		$about = strip_tags(bbcode($about));
		$location = '';
		if($profile['locality'])
			$location .= $profile['locality'];
		if($profile['region']) {
			if($location)
				$location .= ', ';
			$location .= $profile['region'];
		}
		if($profile['country-name']) {
			if($location)
				$location .= ', ';
			$location .= $profile['country-name'];
		}
		$tags = '';
		if($profile['pub_keywords']) {
			$kw = str_replace(',',' ',$profile['pub_keywords']);
			$kw = str_replace('  ',' ',$kw);
			$arr = explode(' ',$profile['pub_keywords']);
			if(count($arr)) {
				for($x = 0; $x < 5; $x ++) {
					if(trim($arr[$x]))
						$tags .= '#' . trim($arr[$x]) . ' ';
				}
			}
		}
		$tags = trim($tags);
	}

	$tpl = get_markup_template('diaspora_profile.tpl');


	$msg = replace_macros($tpl,array(
		'$handle' => $handle,
		'$first' => $first,
		'$last' => $last,
		'$large' => $large,
		'$medium' => $medium,
		'$small' => $small,
		'$dob' => $dob,
		'$gender' => $gender,
		'$about' => $about,
		'$location' => $location,
		'$searchable' => $searchable,
		'$tags' => $tags
	));
	logger('profile_change: ' . $msg, LOGGER_ALL);

	$msgtosend = diaspora_msg_build($msg,$a->user,null,$a->user['prvkey'],null,true);
	foreach($recips as $recip) {
		q("insert into queue (`cid`,`network`,`created`,`last`,`content`,`batch`)
			values(%d,'%s','%s','%s','%s',%d)",
			intval($recip['id']),
			dbesc(NETWORK_DIASPORA),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc($msgtosend),
			intval(1)
		);
	}
}