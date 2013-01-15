<div class="mail-list-outside-wrapper">
	<div class="mail-list-sender" >
		<a href="{{$from_url}}" class="mail-list-sender-url" ><img class="mail-list-sender-photo" src="{{$from_photo}}" height="32" width="32" alt="{{$from_name}}" /></a>
		<div class="mail-list-sender-name" >{{$from_name}}</div>
	</div>
	<div class="mail-list-recip" >
		<a href="{{$to_url}}" class="mail-list-recip-url" ><img class="mail-list-recip-photo" src="{{$to_photo}}" height="32" width="32" alt="{{$to_name}}" /></a>
		<div class="mail-list-recip-name" >{{$to_name}}</div>
	</div>
		<div class="mail-list-date">{{$date}}</div>
		<div class="mail-list-subject"><a href="message/{{$id}}" class="mail-list-link">{{$subject}}</a></div>
	<div class="mail-list-delete-wrapper" id="mail-list-delete-wrapper-{{$id}}" >
		<a href="message/dropconv/{{$id}}" onclick="return confirmDelete();"  title="{{$delete}}" class="icon drophide mail-list-delete	delete-icon" onmouseover="imgbright(this);" onmouseout="imgdull(this);" ></a>
	</div>
</div>
</div>
<div class="mail-list-delete-end"></div>

<div class="mail-list-outside-wrapper-end"></div>
