<h1>{{$header}}</h1>

{{if $site}}
<h3>{{$website}} {{$site}}</h3>
{{/if}}


{{if $raters}}
{{foreach $raters as $r}}

<div class="directory-item lframe" id="directory-item-{{$r.xchan_hash}}" >

<div class="contact-photo-wrapper" id="directory-photo-wrapper-{{$r.xchan_hash}}" >
<div class="contact-photo" id="directory-photo-{{$r.xchan_hash}}" >
<a href="{{$r.xchan_url}}" class="directory-profile-link" id="directory-profile-link-{{$r.xchan_hash}}" ><img class="directory-photo-img" src="{{$r.xchan_photo_m}}" alt="{{$r.xchan_addr}}" title="{{$r.xchan_addr}}" /></a>
</div>
</div>
<div class="prep-details contact-info">
<a href="{{$r.xchan_url}}" class="directory-profile-link" id="directory-profile-link-{{$r.xchan_hash}}" ><div class="contact-name">{{$r.xchan_name}}</div></a>
<div class="rating-value">{{$rating_lbl}} <span class="prep-rating-value">{{$r.xlink_rating}}</span></div>
{{if $r.xlink_rating_text}}
<div class="rating-text">{{$rating_text_label}} {{$r.xlink_rating_text}}
</div>
{{/if}}
</div>
<div class="clear"></div>
</div>
{{/foreach}}
{{/if}}





