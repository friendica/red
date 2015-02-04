<div class="directory-item lframe{{if $entry.safe}} safe{{/if}}" id="directory-item-{{$entry.hash}}" >

<div class="contact-photo-wrapper" id="directory-photo-wrapper-{{$entry.hash}}" >
<div class="contact-photo" id="directory-photo-{{$entry.hash}}" >
<a href="{{$entry.profile_link}}" class="directory-profile-link" id="directory-profile-link-{{$entry.hash}}" ><img class="directory-photo-img" src="{{$entry.photo}}" alt="{{$entry.alttext}}" title="{{$entry.alttext}}" /></a>
{{if $entry.connect}}
<div class="directory-connect btn btn-default"><a href="{{$entry.connect}}"><i class="icon-plus connect-icon"></i> {{$entry.conn_label}}</a></div>
{{/if}}
{{if $entry.ignlink}}
<div class="directory-ignore btn btn-default"><a href="{{$entry.ignlink}}"> {{$entry.ignore_label}}</a></div>
{{/if}}
</div>
</div>

<div class='contact-info'>
<div class="contact-name" id="directory-name-{{$entry.hash}}"  ><a href='{{$entry.profile_link}}' >{{$entry.name}}</a>{{if $entry.online}} <i class="icon-asterisk online-now" title="{{$entry.online}}"></i>{{/if}}</div>

{{if $entry.viewrate}}
<div id="dir-rating-wrapper-{{$entry.hash}}" class="directory-rating" >{{if $entry.total_ratings}}<a href="ratings/{{$entry.hash}}"><button class="btn btn-default">{{$entry.total_ratings}}</button></a>{{/if}}
{{if $entry.canrate}}<button class="btn btn-default" onclick="doRatings('{{$entry.hash}}'); return false;" ><i class="icon-pencil"></i></button><span class="required" id="edited-{{$entry.hash}}" style="display: none;" >*</span>{{/if}}
</div>
{{/if}}
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
