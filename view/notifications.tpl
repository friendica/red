
<h1>Notifications</h1>

<p id="notification-listing-desc">
	<a href="/notifications/network" class="button tabs {{if $activetab==network}}active{{endif}}">Network</a>
	<a href="/notifications/home" class="button tabs {{if $activetab==home}}active{{endif}}">Home</a>
	<a href="/notifications/intros" class="button tabs {{if $activetab==intros}}active{{endif}}">Introductions</a>
	<a href="/message" class="button tabs">Messages</a>
</p>
<div class="notification-listing-end"></div>

<div class="notif-network-wrapper">
	$notif_content
</div>
