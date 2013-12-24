<div class="directory-item lframe" id="directory-item-{{$id}}" >
<div class="generic-content-wrapper">

<div class="contact-photo-wrapper" id="directory-photo-wrapper-{{$id}}" >
<div class="contact-photo" id="directory-photo-{{$id}}" >
<a href="{{$profile_link}}" class="directory-profile-link" id="directory-profile-link-{{$id}}" ><img class="directory-photo-img" src="{{$photo}}" alt="{{$alttext}}" title="{{$alttext}}" /></a>
</div>
</div>

<div class="contact-name" id="directory-name-{{$id}}"  >{{$name}}</div>
{{if $entry.connect}}
<div class="directory-connect"><a href="{{$connect}}">{{$conn_label}}</a></div>
{{/if}}
<div class="contact-details">{{$details}}</div>
</div>
</div>
