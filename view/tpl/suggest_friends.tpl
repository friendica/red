<div class="profile-match-wrapper">
	<a href="{{$entry.ignlnk}}" title="{{$entry.ignore}}" class="profile-match-ignore" onclick="return confirmDelete();" ><i class="icon-remove drop-icons"></i></a>
	<div class="profile-match-photo">
		<a href="{{$entry.url}}">
			<img src="{{$entry.photo}}" alt="{{$entry.name}}" width="80" height="80" title="{{$entry.name}} [{{$entry.profile}}]" />
		</a>
	</div>
	<div class="profile-match-break"></div>
	<div class="profile-match-name">
		<a href="{{$entry.url}}" title="{{$entry.name}}">{{$entry.name}}</a>
	</div>
	<div class="profile-match-end"></div>
	{{if $entry.connlnk}}
	<div class="profile-match-connect"><a href="{{$entry.connlnk}}" title="{{$entry.conntxt}}">{{$entry.conntxt}}</a></div>
	{{/if}}
</div>
