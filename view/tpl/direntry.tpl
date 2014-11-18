<div class="directory-item lframe" id="directory-item-{{$entry.id}}" >

<div class="contact-photo-wrapper" id="directory-photo-wrapper-{{$entry.id}}" >
<div class="contact-photo" id="directory-photo-{{$entry.id}}" >
<a href="{{$entry.profile_link}}" class="directory-profile-link" id="directory-profile-link-{{$entry.id}}" ><img class="directory-photo-img" src="{{$entry.photo}}" alt="{{$entry.alttext}}" title="{{$entry.alttext}}" /></a>
{{if $entry.connect}}
<div class="directory-connect btn btn-default"><a href="{{$entry.connect}}"><i class="icon-plus connect-icon"></i> {{$entry.conn_label}}</a></div>
{{/if}}
{{if $entry.ignlink}}
<div class="directory-ignore btn btn-default"><a href="{{$entry.ignlink}}"> {{$entry.ignore_label}}</a></div>
{{/if}}
</div>
</div>

<div class='contact-info'>
<div class="contact-name" id="directory-name-{{$entry.id}}"  ><a href='{{$entry.profile_link}}' >{{$entry.name}}</a>{{if $entry.online}} <i class="icon-asterisk online-now" title="{{$entry.online}}"></i>{{/if}}</div>

{{if $entry.public_forum}}
<div class="contact-forum">
{{$entry.forum_label}} @{{$entry.nickname}}+
</div>
{{/if}}

<div class="contact-details">{{$entry.details}}</div>
{{if $entry.hometown}}
<div class="directory-hometown">{{$entry.hometown}} </div>
{{/if}}
{{if $entry.about}}
<div class="directory-about">{{$entry.about}} </div>
{{/if}}
{{if $entry.homepage}}
<div class="directory-homepage">{{$entry.homepage}}{{$entry.homepageurl}} </div>
{{/if}}
{{if $entry.kw}}
<div class="directory-keywords">{{$entry.kw}} {{$entry.keywords}}</div>
{{/if}}
</div>
</div>
