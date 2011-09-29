
<div id="profile-tabs-wrapper" >
	<a href="$url" id="profile-tab-status-link" class="profile-tabs button" >$status</a>
	<a href="$url?tab=profile" id="profile-tab-profile-link" class="profile-tabs button" >$profile</a>
	<a href="$phototab" id="profile-tab-photos-link" class="profile-tabs button" >$photos</a>
	{{ if $events }}<a href="events" id="profile-tab-events-link" class="profile-tabs button" >$events</a>{{ endif }}
	{{ if $notes }}<a href="notes" id="profile-tab-notes-link" class="profile-tabs button" >$notes</a>{{ endif }}
<div id="profile-tabs-end"></div>
</div>
