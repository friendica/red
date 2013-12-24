<div class="directory-item lframe" id="directory-item-{{$entry.id}}" >
<div class="generic-content-wrapper">

<div class="contact-photo-wrapper" id="directory-photo-wrapper-{{$entry.id}}" >
<div class="contact-photo" id="directory-photo-{{$entry.id}}" >
<a href="{{$entry.profile_link}}" class="directory-profile-link" id="directory-profile-link-{{$entry.id}}" ><img class="directory-photo-img" src="{{$entry.photo}}" alt="{{$entry.alttext}}" title="{{$entry.alttext}}" /></a>
</div>
</div>

<div class="contact-name" id="directory-name-{{$entry.id}}"  ><span onclick="dirdetails('{{$entry.hash}}');" class="fakelink" >{{$entry.name}}</span></div>
{{if $entry.connect}}
<div class="directory-connect"><a href="{{$entry.connect}}">{{$entry.conn_label}}</a></div>
{{/if}}
<div class="contact-details">{{$entry.details}}</div>
</div>
</div>
