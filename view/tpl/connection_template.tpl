<div class="contact-entry-wrapper" id="contact-entry-wrapper-{{$contact.id}}" >
	<div class="contact-entry-photo-wrapper" >
		<a href="{{$contact.url}}" title="{{$contact.img_hover}}" ><img class="contact-block-img {{if $contact.classes}}{{$contact.classes}}{{/if}}" src="{{$contact.thumb}}" alt="{{$contact.name}}" /></a>
	</div>
	<div class="contact-entry-photo-end" ></div>
	<a href="{{$contact.url}}" title="{{$contact.img_hover}}" ><div class="contact-entry-name" id="contact-entry-name-{{$contact.id}}" >{{$contact.name}}</div></a>
	<div class="contact-entry-edit btn btn-default"><a href="{{$contact.link}}"><i class="icon-pencil connection-edit-icons"></i> {{$contact.edit}}</a></div>
	<div class="contact-entry-end" ></div>
</div>
