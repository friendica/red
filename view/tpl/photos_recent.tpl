<h3>{{$title}}</h3>
{{if $can_post}}
<a id="photo-top-upload-link" href="{{$upload.1}}">{{$upload.0}}</a>
{{/if}}

<div class="photos">
{{foreach $photos as $photo}}
	{{include file="photo_top.tpl"}}
{{/foreach}}
</div>
<div class="photos-end"></div>
