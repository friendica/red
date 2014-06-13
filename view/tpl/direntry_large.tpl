<div class="directory-popup-item lframe" id="directory-item-{{$id}}" >
<div class="generic-content-wrapper">

<div class="contact-photo-wrapper" id="directory-photo-wrapper-{{$id}}" >
	<div class="contact-photo dirpopup" id="directory-photo-{{$id}}" >
	<a href="{{$profile_link}}" class="directory-profile-link" id="directory-profile-link-{{$id}}" ><img class="directory-photo-img" style="height:175px; width:175px;" src="{{$photo}}" alt="{{$alttext}}" title="{{$alttext}}" /></a>
	</div>
	<div class="contact-photo dirpopup" id="directory-qr-{{$id}}" >
	<img class="directory-photo-img" style="height:175px; width:175px;" src="photo/qr?f=&qr={{$qrlink}}" alt="QR" title="{{$qrlink}}" />
	</div>
</div>

<div class="clear"></div>


<div class="contact-name" id="directory-name-{{$id}}"  >{{$name}}{{if $online}} <i class="icon-asterisk online-now" title="{{$online}}"></i>{{/if}}</div>
{{if $connect}}
<div class="directory-connect btn btn-default"><a href="{{$connect}}"><i class="icon-plus connect-icon"></i> {{$conn_label}}</a></div>
{{/if}}

<div class="contact-webbie">{{$address}}</div>

<div class="contact-details">{{$details}}</div>
{{if $marital}}
<div class="directory-marital">{{$marital}} </div>
{{/if}}
{{if $sexual}}
<div class="directory-sexual">{{$sexual}} </div>
{{/if}}
{{if $homepage}}
<div class="directory-homepage">{{$homepage}} </div>
{{/if}}
{{if $hometown}}
<div class="directory-hometown">{{$hometown}} </div>
{{/if}}
{{if $about}}
<div class="directory-about">{{$about}} </div>
{{/if}}
{{if $kw}}
<div class="directory-keywords">{{$kw}} {{$keywords}}</div>
{{/if}}
</div>
</div>
