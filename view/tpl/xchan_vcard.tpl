<div class="vcard">
<div class="fn">{{$name}}</div>
<div id="profile-photo-wrapper"><img class="vcard-photo photo" src="{{$photo}}" alt="{{$name}}" /></div>
</div>


{{if $mode != 'mail'}}	
<div id="profile-extra-links">
<ul>
{{if $connect}}
	<li><a id="follow-link" href="follow?f=&url={{$follow}}">{{$connect}}</a></li>
{{/if}}
{{if $newwin}}
	<li><a id="visit-chan-link" href="{{$url}}" title="{{$newtit}}" target="_blank" >{{$newwin}}</a></li>
{{/if}}
</ul>


</div>
{{/if}}
