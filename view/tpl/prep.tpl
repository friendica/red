<h1>{{$header}}</h1>

{{if $raters}}
{{foreach $raters as $r}}

<div class="directory-item lframe" id="directory-item-{{$r.xchan_hash}}" >

<div class="contact-photo-wrapper" id="directory-photo-wrapper-{{$r.xchan_hash}}" >
<div class="contact-photo" id="directory-photo-{{$r.xchan_hash}}" >
<a href="{{$r.xchan_url}}" class="directory-profile-link" id="directory-profile-link-{{$r.xchan_hash}}" ><img class="directory-photo-img" src="{{$r.xchan_photo_m}}" alt="{{$r.xchan_addr}}" title="{{$r.xchan_addr}}" /></a>
</div>
</div>
<div class="prep-details">
<a href="{{$r.xchan_url}}" class="directory-profile-link" id="directory-profile-link-{{$r.xchan_hash}}" ><div class="contact-name">{{$r.xchan_name}}</div></a>
{{$rating_lbl}} {{$r.xlink_rating}}
{{if $r.xlink_rating_text}}
{{$rating_text_label}} {{$r.xlink_rating_text}}
{{/if}}
</div>
<div class="clear"></div>
</div>
{{/foreach}}
{{/if}}





