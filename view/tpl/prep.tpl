<h1>{{$header}}</h1>

{{if $poi}}

<div class="directory-item lframe" id="directory-item-{{$poi.xchan_hash}}" >

<div class="contact-photo-wrapper" id="directory-photo-wrapper-{{$poi.xchan_hash}}" >
<div class="contact-photo" id="directory-photo-{{$poi.xchan_hash}}" >
<a href="{{$poi.xchan_url}}" class="directory-profile-link" id="directory-profile-link-{{$poi.xchan_hash}}" ><img class="directory-photo-img" src="{{$poi.xchan_photo_l}}" alt="{{$poi.xchan_addr}}" title="{{$poi.xchan_addr}}" /></a>
<div class="contact-name">{{$poi.xchan_name}}</div>
</div>
</div>
{{/if}}

{{if $raters}}
{{foreach $raters as $r}}

<div class="directory-item lframe" id="directory-item-{{$r.xchan_hash}}" >

<div class="contact-photo-wrapper" id="directory-photo-wrapper-{{$r.xchan_hash}}" >
<div class="contact-photo" id="directory-photo-{{$r.xchan_hash}}" >
<a href="{{$r.xchan_url}}" class="directory-profile-link" id="directory-profile-link-{{$r.xchan_hash}}" ><img class="directory-photo-img" src="{{$r.xchan_photo_l}}" alt="{{$r.xchan_addr}}" title="{{$r.xchan_addr}}" /></a>
<div class="contact-name">{{$r.xchan_name}}</div>
</div>
Rating: {{$r.xlink_rating}}
{{if $r.xlink_rating_text}}
Reason: {{$r.xlink_rating_text}}
{{/if}}
</div>
{{/foreach}}
{{/if}}





