<?php

function advanced_profile(&$a) {

$o .= '';

$o .= '<h2>' . t('Profile') . '</h2>';

if($a->profile['name']) {
	$lbl_fullname = t('Full Name:');
	$fullname = $a->profile['name'];

$o .= <<< EOT
<div id="advanced-profile-name-wrapper" >
<div id="advanced-profile-name-text" class="advanced-profile-label">$lbl_fullname</div>
<div id="advanced-profile-name" class="advanced-profile-content">$fullname</div>
</div>
<div id="advanced-profile-name-end"></div>
EOT;
}

if($a->profile['gender']) {
	$lbl_gender = t('Gender:');
	$gender = $a->profile['gender'];

$o .= <<< EOT
<div id="advanced-profile-gender-wrapper" >
<div id="advanced-profile-gender-text" class="advanced-profile-label">$lbl_gender</div>
<div id="advanced-profile-gender" class="advanced-profile-content">$gender</div>
</div>
<div id="advanced-profile-gender-end"></div>
EOT;
}

if(($a->profile['dob']) && ($a->profile['dob'] != '0000-00-00')) {
	$lbl_birthday = t('Birthday:');

$o .= <<< EOT
<div id="advanced-profile-dob-wrapper" >
<div id="advanced-profile-dob-text" class="advanced-profile-label">$lbl_birthday</div>
EOT;

// If no year, add an arbitrary one so just we can parse the month and day.

$year_bd_format = t('j F, Y');
$short_bd_format = t('j F');

$o .= '<div id="advanced-profile-dob" class="advanced-profile-content">' 
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
<div id="advanced-profile-age-text" class="advanced-profile-label">$lbl_age</div>
<div id="advanced-profile-age" class="advanced-profile-content">$age</div>
</div>
<div id="advanced-profile-age-end"></div>
EOT;
}

if($a->profile['marital']) {
	$lbl_marital = t('<span class="heart">&hearts;</span> Status:');
	$marital = $a->profile['marital'];

$o .= <<< EOT
<div id="advanced-profile-marital-wrapper" >
<div id="advanced-profile-marital-text" class="advanced-profile-label">$lbl_marital</div>
<div id="advanced-profile-marital" class="advanced-profile-content">$marital</div>
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
<div id="advanced-profile-sexual-text" class="advanced-profile-label">$lbl_sexual</div>
<div id="advanced-profile-sexual" class="advanced-profile-content">$sexual</div>
</div>
<div id="advanced-profile-sexual-end"></div>
EOT;
}

if($a->profile['homepage']) {
	$lbl_homepage = t('Homepage:');
	$homepage = linkify($a->profile['homepage']);
$o .= <<< EOT
<div id="advanced-profile-homepage-wrapper" >
<div id="advanced-profile-homepage-text" class="advanced-profile-label">$lbl_homepage</div>
<div id="advanced-profile-homepage" class="advanced-profile-content">$homepage</div>
</div>
<div id="advanced-profile-homepage-end"></div>
EOT;
}

if($a->profile['politic']) {
	$lbl_politic = t('Political Views:');
	$politic = $a->profile['politic'];
$o .= <<< EOT
<div id="advanced-profile-politic-wrapper" >
<div id="advanced-profile-politic-text" class="advanced-profile-label">$lbl_politic</div>
<div id="advanced-profile-politic" class="advanced-profile-content">$politic</div>
</div>
<div id="advanced-profile-politic-end"></div>
EOT;
}

if($a->profile['religion']) {
	$lbl_religion = t('Religion:');
	$religion = $a->profile['religion'];
$o .= <<< EOT
<div id="advanced-profile-religion-wrapper" >
<div id="advanced-profile-religion-text" class="advanced-profile-label">$lbl_religion</div>
<div id="advanced-profile-religion" class="advanced-profile-content">$religion</div>
</div>
<div id="advanced-profile-religion-end"></div>
EOT;
}
if($txt = prepare_text($a->profile['about'])) {
	$lbl_about = t('About:');
$o .= <<< EOT
<div id="advanced-profile-about-wrapper" >
<div id="advanced-profile-about-text" class="advanced-profile-label">$lbl_about</div>
<br />
<div id="advanced-profile-about" class="advanced-profile-content">$txt</div>
</div>
<div id="advanced-profile-about-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['interest'])) {
	$lbl_interests = t('Hobbies/Interests:');
$o .= <<< EOT
<div id="advanced-profile-interest-wrapper" >
<div id="advanced-profile-interest-text" class="advanced-profile-label">$lbl_interests</div>
<br />
<div id="advanced-profile-interest" class="advanced-profile-content">$txt</div>
</div>
<div id="advanced-profile-interest-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['contact'])) {
	$lbl_contact = t('Contact information and Social Networks:');
$o .= <<< EOT
<div id="advanced-profile-contact-wrapper" >
<div id="advanced-profile-contact-text" class="advanced-profile-label">$lbl_contact</div>
<br />
<div id="advanced-profile-contact" class="advanced-profile-content">$txt</div>
</div>
<div id="advanced-profile-contact-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['music'])) {
	$lbl_music = t('Musical interests:');
$o .= <<< EOT
<div id="advanced-profile-music-wrapper" >
<div id="advanced-profile-music-text" class="advanced-profile-label">$lbl_music</div>
<br />
<div id="advanced-profile-music" class="advanced-profile-content">$txt</div>
</div>
<div id="advanced-profile-music-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['book'])) {
	$lbl_book = t('Books, literature:');
$o .= <<< EOT
<div id="advanced-profile-book-wrapper" >
<div id="advanced-profile-book-text" class="advanced-profile-label">$lbl_book</div>
<br />
<div id="advanced-profile-book" class="advanced-profile-content">$txt</div>
</div>
<div id="advanced-profile-book-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['tv'])) {
	$lbl_tv = t('Television:');
$o .= <<< EOT
<div id="advanced-profile-tv-wrapper" >
<div id="advanced-profile-tv-text" class="advanced-profile-label">$lbl_tv</div>
<br />
<div id="advanced-profile-tv" class="advanced-profile-content">$txt</div>
</div>
<div id="advanced-profile-tv-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['film'])) {
	$lbl_film = t('Film/dance/culture/entertainment:');
$o .= <<< EOT
<div id="advanced-profile-film-wrapper" >
<div id="advanced-profile-film-text" class="advanced-profile-label">$lbl_film</div>
<br />
<div id="advanced-profile-film" class="advanced-profile-content">$txt</div>
</div>
<div id="advanced-profile-film-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['romance'])) {
	$lbl_romance = t('Love/Romance:');
$o .= <<< EOT
<div id="advanced-profile-romance-wrapper" >
<div id="advanced-profile-romance-text" class="advanced-profile-label">$lbl_romance</div>
<br />
<div id="advanced-profile-romance" class="advanced-profile-content">$txt</div>
</div>
<div id="advanced-profile-romance-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['work'])) {
	$lbl_work = t('Work/employment:');
$o .= <<< EOT
<div id="advanced-profile-work-wrapper" >
<div id="advanced-profile-work-text" class="advanced-profile-label">$lbl_work</div>
<br />
<div id="advanced-profile-work" class="advanced-profile-content">$txt</div>
</div>
<div id="advanced-profile-work-end"></div>
EOT;
}

if($txt = prepare_text($a->profile['education'])) {
	$lbl_education = t('School/education:');
$o .= <<< EOT
<div id="advanced-profile-education-wrapper" >
<div id="advanced-profile-education-text" class="advanced-profile-label">$lbl_education</div>
<br />
<div id="advanced-profile-education" class="advanced-profile-content">$txt</div>
</div>
<div id="advanced-profile-education-end"></div>
EOT;
}

return $o;
}
