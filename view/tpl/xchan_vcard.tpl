<div class="vcard">
<div class="fn">{{$name}}</div>
<div id="profile-photo-wrapper"><a href="{{$link}}"><img class="vcard-photo photo" src="{{$photo}}" alt="{{$name}}" /></a></div>
</div>


{{if $mode != 'mail'}}	
{{if $connect}}
	<a href="follow?f=&url={{$follow}}" class="rconnect"><i class="icon-plus connect-icon"></i> {{$connect}}</a>
{{/if}}
{{/if}}
