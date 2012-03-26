{{ if $page }}
<div>$page</div>
{{ endif }}

{{ if $lastusers_title }}
<h3 style="margin-top:0px;">Help or @NewHere ?</h3>
<a href="https://helpers.pyxis.uberspace.de/profile/helpers" style="margin-left: 10px; " title="Friendica Support" target="blank">Friendica Support</a><br>
<a href="https://letstalk.pyxis.uberspace.de/profile/letstalk" style="margin-left: 10px; " title="Let's talk" target="blank">Let's talk</a><br>
<a href="http://newzot.hydra.uberspace.de/profile/newzot" title="Local Friendica" style="margin-left: 10px; " target="blank">Local Friendica</a>
<a href="http://kakste.com/profile/newhere" title="#NewHere" style="margin-left: 10px; " target="blank">NewHere</a>
{{ endif }}

{{ if $lastusers_title }}
<h3>Connectable Services</h3>
<div id="right_service_icons" style="margin-left: 11px; margin-top: 5px;">
<a href="$url/facebook"><img alt="Facebook" src="view/theme/diabook-blue/icons/facebook.png" title="Facebook"></a>
<a href="$url/settings/connectors"><img alt="StatusNet" src="view/theme/diabook-blue/icons/StatusNet.png?" title="StatusNet"></a>
<a href="$url/settings/connectors"><img alt="LiveJournal" src="view/theme/diabook-blue/icons/livejournal.png?" title="LiveJournal"></a>
<a href="$url/settings/connectors"><img alt="Posterous" src="view/theme/diabook-blue/icons/posterous.png?" title="Posterous"></a>
<a href="$url/settings/connectors"><img alt="Tumblr" src="view/theme/diabook-blue/icons/tumblr.png?" title="Tumblr"></a>
<a href="$url/settings/connectors"><img alt="Twitter" src="view/theme/diabook-blue/icons/twitter.png?" title="Twitter"></a>
<a href="$url/settings/connectors"><img alt="WordPress" src="view/theme/diabook-blue/icons/wordpress.png?" title="WordPress"></a>
<a href="$url/settings/connectors"><img alt="E-Mail" src="view/theme/diabook-blue/icons/email.png?" title="E-Mail"></a>
</div>
{{ endif }}

{{ if $lastusers_title }}
<h3>Find Friends</h3>
<a class="$nv.directory.2" href="$nv.directory.0" style="margin-left: 10px; " title="$nv.directory.3" >$nv.directory.1</a><br>
<a class="$nv.match.2" href="$nv.match.0" style="margin-left: 10px; " title="$nv.match.3" >$nv.match.1</a><br>
<a class="$nv.suggest.2" href="$nv.suggest.0" style="margin-left: 10px; " title="$nv.suggest.3" >$nv.suggest.1</a>
<a class="$nv.invite.2" href="$nv.invite.0" style="margin-left: 10px; " title="$nav.invite.3" >$nv.invite.1</a>			
{{ endif }}

{{ if $lastusers_title }}
<h3>PostIt to Friendica</h3>
<div style="padding-left: 8px;"><span ><a href="$fostitJS" title="PostIt">Post to Friendica</a> from anywhere by bookmarking the Link.</span></div>
{{ endif }}

{{ if $lastusers_title }}
<h3>$lastusers_title</h3>
<div id='lastusers-wrapper' class='items-wrapper'>
{{ for $lastusers_items as $i }}
	$i
{{ endfor }}
</div>
{{ endif }}

{{ if $activeusers_title }}
<h3>$activeusers_title</h3>
<div class='items-wrapper'>
{{ for $activeusers_items as $i }}
	$i
{{ endfor }}
</div>
{{ endif }}

{{ if $photos_title }}
<h3>$photos_title</h3>
<div id='ra-photos-wrapper' class='items-wrapper'>
{{ for $photos_items as $i }}
	$i
{{ endfor }}
</div>
{{ endif }}


{{ if $like_title }}
<h3>$like_title</h3>
<ul id='likes'>
{{ for $like_items as $i }}
	<li id='ra-photos-wrapper'>$i</li>
{{ endfor }}
</ul>
{{ endif }}
