<div class="generic-content-wrapper" id="mail-list-wrapper">
	<a href="{{$from_url}}" class ="mail-list" ><img class="mail-list-sender-photo" src="{{$from_photo}}" alt="{{$from_name}}" /></a>
	<span class="mail-list">{{$from_name}}</span>
	<span class="mail-list" {{if $seen}}seen{{else}}unseen{{/if}}><a href="message/{{$id}}" class="mail-link">{{$subject}}</a></span>
	<span class="mail-list" title="{{$date}}">{{$date}}</span>
	<span class="mail-list mail-list-remove"><a href="message/dropconv/{{$id}}" onclick="return confirmDelete();"  title="{{$delete}}" ><i class="icon-remove mail-icons drop-icons"></i></a></span>
	<div class="clear">&nbsp;</div>
</div>
