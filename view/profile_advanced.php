<?php

$o .= '';

$o .= <<< EOT

<h2>Profile</h2>


EOT;

if($a->profile['name']) {
$o .= <<< EOT
<div id="advanced-profile-name-wrapper" >
<div id="advanced-profile-name-text">Full Name:</div>
<div id="advanced-profile-name">{$a->profile['name']}</div>
</div>
<div id="advanced-profile-name-end"></div>
EOT;
}

if($a->profile['gender']) {
$o .= <<< EOT
<div id="advanced-profile-gender-wrapper" >
<div id="advanced-profile-gender-text">Gender:</div>
<div id="advanced-profile-gender">{$a->profile['gender']}</div>
</div>
<div id="advanced-profile-gender-end"></div>
EOT;
}

if(($a->profile['dob']) && ($a->profile['dob'] != '0000-00-00')) {
$o .= <<< EOT
<div id="advanced-profile-dob-wrapper" >
<div id="advanced-profile-dob-text">Birthday:</div>
EOT;

// If no year, add an arbitrary one so just we can parse the month and day.

$o .= '<div id="advanced-profile-dob">' 
	. ((intval($a->profile['dob'])) 
		? datetime_convert('UTC',date_default_timezone_get(),$a->profile['dob'],'j F, Y')
		: datetime_convert('UTC',date_default_timezone_get(),'2001-' . substr($a->profile['dob'],6),'j F')) 
	. "</div>\r\n</div>";

$o .= '<div id="advanced-profile-dob-end"></div>';

}

if($age = age($a->profile['dob'],$a->profile['timezone'],'')) {
$o .= <<< EOT
<div id="advanced-profile-age-wrapper" >
<div id="advanced-profile-age-text">Age:</div>
<div id="advanced-profile-age">$age</div>
</div>
<div id="advanced-profile-age-end"></div>
EOT;
}

if($a->profile['marital']) {
$o .= <<< EOT
<div id="advanced-profile-marital-wrapper" >
<div id="advanced-profile-marital-text"><span class="heart">&hearts;</span> Status:</div>
<div id="advanced-profile-marital">{$a->profile['marital']}</div>
</div>
<div id="advanced-profile-marital-end"></div>
EOT;
}

if($a->profile['sexual']) {
$o .= <<< EOT
<div id="advanced-profile-sexual-wrapper" >
<div id="advanced-profile-sexual-text">Sexual Preference:</div>
<div id="advanced-profile-sexual">{$a->profile['sexual']}</div>
</div>
<div id="advanced-profile-sexual-end"></div>
EOT;
}

if($a->profile['homepage']) {
$o .= <<< EOT
<div id="advanced-profile-homepage-wrapper" >
<div id="advanced-profile-homepage-text">Homepage:</div>
<div id="advanced-profile-homepage">{$a->profile['homepage']}</div>
</div>
<div id="advanced-profile-homepage-end"></div>
EOT;
}

if($a->profile['politic']) {
$o .= <<< EOT
<div id="advanced-profile-politic-wrapper" >
<div id="advanced-profile-politic-text">Political Views:</div>
<div id="advanced-profile-politic">{$a->profile['politic']}</div>
</div>
<div id="advanced-profile-politic-end"></div>
EOT;
}

if($a->profile['religion']) {
$o .= <<< EOT
<div id="advanced-profile-religion-wrapper" >
<div id="advanced-profile-religion-text">Religion:</div>
<div id="advanced-profile-religion">{$a->profile['religion']}</div>
</div>
<div id="advanced-profile-religion-end"></div>
EOT;
}

if($txt = bbcode($a->profile['about'])) {
$o .= <<< EOT
<div id="advanced-profile-about-wrapper" >
<div id="advanced-profile-about-text">About:</div>
<br />
<div id="advanced-profile-about">$txt</div>
</div>
<div id="advanced-profile-about-end"></div>
EOT;
}

if($txt = bbcode($a->profile['interest'])) {
$o .= <<< EOT
<div id="advanced-profile-interest-wrapper" >
<div id="advanced-profile-interest-text">Hobbies/Interests:</div>
<br />
<div id="advanced-profile-interest">$txt</div>
</div>
<div id="advanced-profile-interest-end"></div>
EOT;
}

if($txt = bbcode($a->profile['contact'])) {
$o .= <<< EOT
<div id="advanced-profile-contact-wrapper" >
<div id="advanced-profile-contact-text">Contact information and Social Networks:</div>
<br />
<div id="advanced-profile-contact">$txt</div>
</div>
<div id="advanced-profile-contact-end"></div>
EOT;
}

if($txt = bbcode($a->profile['music'])) {
$o .= <<< EOT
<div id="advanced-profile-music-wrapper" >
<div id="advanced-profile-music-text">Musical interests:</div>
<br />
<div id="advanced-profile-music">$txt</div>
</div>
<div id="advanced-profile-music-end"></div>
EOT;
}

if($txt = bbcode($a->profile['book'])) {
$o .= <<< EOT
<div id="advanced-profile-book-wrapper" >
<div id="advanced-profile-book-text">Books, literature:</div>
<br />
<div id="advanced-profile-book">$txt</div>
</div>
<div id="advanced-profile-book-end"></div>
EOT;
}

if($txt = bbcode($a->profile['tv'])) {
$o .= <<< EOT
<div id="advanced-profile-tv-wrapper" >
<div id="advanced-profile-tv-text">Television:</div>
<br />
<div id="advanced-profile-tv">$txt</div>
</div>
<div id="advanced-profile-tv-end"></div>
EOT;
}

if($txt = bbcode($a->profile['film'])) {
$o .= <<< EOT
<div id="advanced-profile-film-wrapper" >
<div id="advanced-profile-film-text">Film/dance/culture/entertainment:</div>
<br />
<div id="advanced-profile-film">$txt</div>
</div>
<div id="advanced-profile-film-end"></div>
EOT;
}

if($txt = bbcode($a->profile['romance'])) {
$o .= <<< EOT
<div id="advanced-profile-romance-wrapper" >
<div id="advanced-profile-romance-text">Love/romance:</div>
<br />
<div id="advanced-profile-romance">$txt</div>
</div>
<div id="advanced-profile-romance-end"></div>
EOT;
}

if($txt = bbcode($a->profile['work'])) {
$o .= <<< EOT
<div id="advanced-profile-work-wrapper" >
<div id="advanced-profile-work-text">Work/employment:</div>
<br />
<div id="advanced-profile-work">$txt</div>
</div>
<div id="advanced-profile-work-end"></div>
EOT;
}

if($txt = bbcode($a->profile['education'])) {
$o .= <<< EOT
<div id="advanced-profile-education-wrapper" >
<div id="advanced-profile-education-text">School/education:</div>
<br />
<div id="advanced-profile-education">$txt</div>
</div>
<div id="advanced-profile-education-end"></div>
EOT;
}

