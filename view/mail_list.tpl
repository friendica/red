<div class="mail-list-outside-wrapper">
	<div class="mail-list-sender" >
		<a href="$from_url" class="mail-list-sender-url" ><img class="mail-list-sender-photo" src="$from_photo" alt="$from_name" /></a>
	</div>
	<div class="mail-list-detail">
		<div class="mail-list-sender-name" >$from_name</div>
		<div class="mail-list-date">$date</div>
		<div class="mail-list-subject"><a href="message/$id" class="mail-list-link">$subject</a></div>
	</div>
</div>
<div class="mail-list-delete-wrapper" id="mail-list-delete-wrapper-$id" ><a href="message/dropconv/$id" onclick="return confirmDelete();" ><img src="images/b_drophide.gif" alt="$delete" title="$delete" id="mail-list-delete-icon-$id" class="mail-list-delete-icon" onmouseover="imgbright(this);" onmouseout="imgdull(this);" ></a></div><div class="mail-list-delete-end"></div>

<div class="mail-list-outside-wrapper-end"></div>
