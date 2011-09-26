
<div class="tabs-wrapper" >
	<a href="$url" id="profile-tab-status-link" class="tabs {{if $activetab==posts}}active{{endif}}" >$status</a>
	<a href="$url?tab=profile" id="profile-tab-profile-link" class="tabs {{if $activetab==profile}}active{{endif}}" >$profile</a>
	<a href="$phototab" id="profile-tab-photos-link" class="tabs {{if $activetab==photos}}active{{endif}}" >$photos</a>
	{{ if $events }}<a href="events" id="profile-tab-events-link" class="tabs {{if $activetab==events}}active{{endif}}" >$events</a>{{ endif }}
	{{ if $notes }}<a href="notes" id="profile-tab-notes-link" class="tabs {{if $activetab==notes}}active{{endif}}" >$notes</a>{{ endif }}	
<div class="tabs-end"></div>
</div>
