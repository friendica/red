<?php

function advanced_profile(&$a) {

$o .= '';

$o .= '<h2>' . t('Profile') . '</h2>';

if($a->profile['name']) {
	$lbl_fullname = t('Full Name:');
	$fullname = $a->profile['name'];

$o .= <<< EOT
<div id="advanced-profile-name-wrapper" >
<div id="advanced-profile-name-text">$lbl_fullname</div>
<div id="advanced-profile-name">$fullname</div>
</div>
<div id="advanced-profile-name-end"></div>
EOT;
}

if($a->profile['gender']) {
	$lbl_gender = t('Gender:');
	$gender = $a->profile['gender'];

$o .= <<< EOT
<div id="advanced-profile-gender-wrapper" >
<div id="advanced-profile-gender-text">$lbl_gender</div>
<div id="advanced-profile-gender">$gender</div>
</div>
<div id="advanced-profile-gender-end"></div>
EOT;
}

if(($a->profile['dob']) && ($a->profile['dob'] != '0000-00-00')) {
	$lbl_birthday = t('Birthday:');

$o .= <<< EOT
<div id="advanced-profile-dob-wrapper" >
<div id="advanced-profile-dob-text">$lbl_birthday</div>
EOT;

// If no year, add an arbitrary one so just we can parse the month and day.

$year_bd_format = t('j F, Y');
$short_bd_format = t('j F');

$o .= '<div id="advanced-profile-dob">' 
	. ((intval($a->profile['dob'])) 
		? day_translate(datetime_convert('UTC','UTC',$a->profile['dob'] . ' 00:00 +00:00',$year_bd_format))
		: day_translate(datetime_convert('UTC','UTC','2001-' . substr($a->profile['dob'],6) . ' 00:00 +00:00',$short_bd_format))) 
	. "</div>\r\n</div>";

$o .= '<div id="advanced-profile-dob-end"></div>';

}

if($age = age($a->profile['dob'],$a->profile['timezone'],'')) {
	$lbl_age = t('Age:');
$o .= <<< EOT
<div id="advanced-profile-age-wrapper" >
<div id="advanced-profile-age-text">$lbl_age</div>
<div id="advanced-profile-age">$age</div>
</div>
<div id="advanced-profile-age-end"></div>
EOT;
}

if($a->profile['marital']) {
	$lbl_marital = t('<span class="heart">&hearts;</span> Status:');
	$marital = $a->profile['marital'];

$o .= <<< EOT
<div id="advanced-profile-marital-wrapper" >
<div id="advanced-profile-marital-text">$lbl_marital</div>
<div id="advanced-profile-marital">$marital</div>
EOT;

if($a->profile['with']) {
	$with = $a->profile['with'];
	$o .= "<div id=\"advanced-profile-with\">($with)</div>";
}
$o .= <<< EOT
</div>
<div id="advanced-profile-marital-end"></div>
EOT;
}

if($a->profile['sexual']) {
	$lbl_sexual = t('Sexual Preference:');
	$sexual = $a->profile['sexual'];

$o .= <<< EOT
<div id="advanced-profile-sexual-wrapper" >
<div id="advanced-profile-sexual-text">$lbl_sexual</div>
<div id="advanced-profile-sexual">$sexual</div>
</div>
<div id="advanced-profile-sexual-end"></div>
EOT;
}

if($a->profile['homepage']) {
	$lbl_homepage = t('Homepage:');
	$homepage = linkify($a->profile['homepage']);
$o .= <<< EOT
<div id="advanced-profile-homepage-wrapper" >
<div id="advanced-profile-homepage-text">$lbl_homepage</div>
<div id="advanced-profile-homepage">$homepage</div>
</div>
<div id="advanced-profile-homepage-end"></div>
EOT;
}

if($a->profile['politic']) {
	$lbl_politic = t('Political Views:');
	$politic = $a->profile['politic'];
$o .= <<< EOT
<div id="advanced-profile-politic-wrapper" >
<div id="advanced-profile-politic-text">$lbl_politic</div>
<div id="advanced-profile-politic">$politic</div>
</div>
<div id="advanced-profile-politic-end"></div>
EOT;
}

if($a->profile['religion']) {
	$lbl_religion = t('Religion:');
	$religion = $a->profile['religion'];
$o .= <<< EOT
<div id="advanced-profile-religion-wrapper" >
<div id="advanced-profile-religion-text">$lbl_religion</div>
<div id="advanced-profile-religion">$religion</div>
</div>
<div id="advanced-profile-religion-end"></div>
EOT;
}
if($txt = prepare_text($a->profile['about'])) {
	$lbl_about = t('About:');
$o .= <<< EOT
<div id="advanced-profile-about-wrapper" >
<div id="advanced-profile-about-text">$lbl_about</div>
<br />
<div id="advanced-profile-about">$txt</div>
</div>
<div id="advanced-profile-about-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['interest'])) {
	$lbl_interests = t('Hobbies/Interests:');
$o .= <<< EOT
<div id="advanced-profile-interest-wrapper" >
<div id="advanced-profile-interest-text">$lbl_interests</div>
<br />
<div id="advanced-profile-interest">$txt</div>
</div>
<div id="advanced-profile-interest-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['contact'])) {
	$lbl_contact = t('Contact information and Social Networks:');
$o .= <<< EOT
<div id="advanced-profile-contact-wrapper" >
<div id="advanced-profile-contact-text">$lbl_contact</div>
<br />
<div id="advanced-profile-contact">$txt</div>
</div>
<div id="advanced-profile-contact-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['music'])) {
	$lbl_music = t('Musical interests:');
$o .= <<< EOT
<div id="advanced-profile-music-wrapper" >
<div id="advanced-profile-music-text">$lbl_music</div>
<br />
<div id="advanced-profile-music">$txt</div>
</div>
<div id="advanced-profile-music-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['book'])) {
	$lbl_book = t('Books, literature:');
$o .= <<< EOT
<div id="advanced-profile-book-wrapper" >
<div id="advanced-profile-book-text">$lbl_book</div>
<br />
<div id="advanced-profile-book">$txt</div>
</div>
<div id="advanced-profile-book-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['tv'])) {
	$lbl_tv = t('Television:');
$o .= <<< EOT
<div id="advanced-profile-tv-wrapper" >
<div id="advanced-profile-tv-text">$lbl_tv</div>
<br />
<div id="advanced-profile-tv">$txt</div>
</div>
<div id="advanced-profile-tv-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['film'])) {
	$lbl_film = t('Film/dance/culture/entertainment:');
$o .= <<< EOT
<div id="advanced-profile-film-wrapper" >
<div id="advanced-profile-film-text">$lbl_film</div>
<br />
<div id="advanced-profile-film">$txt</div>
</div>
<div id="advanced-profile-film-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['romance'])) {
	$lbl_romance = t('Love/Romance:');
$o .= <<< EOT
<div id="advanced-profile-romance-wrapper" >
<div id="advanced-profile-romance-text">$lbl_romance</div>
<br />
<div id="advanced-profile-romance">$txt</div>
</div>
<div id="advanced-profile-romance-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['work'])) {
	$lbl_work = t('Work/employment:');
$o .= <<< EOT
<div id="advanced-profile-work-wrapper" >
<div id="advanced-profile-work-text">$lbl_work</div>
<br />
<div id="advanced-profile-work">$txt</div>
</div>
<div id="advanced-profile-work-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['education'])) {
	$lbl_education = t('School/education:');
$o .= <<< EOT
<div id="advanced-profile-education-wrapper" >
<div id="advanced-profile-education-text">$lbl_education</div>
<br />
<div id="advanced-profile-education">$txt</div>
</div>
<div id="advanced-profile-education-end"></div>
EOT;
}

return $o;
}
