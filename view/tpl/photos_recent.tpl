<div class="section-title-wrapper">
	{{if $can_post}}
	<button class="btn btn-xs btn-success pull-right" title="{{$usage}}" onclick="openClose('photo-upload-form');"><i class="icon-upload"></i>&nbsp;{{$upload.0}}</button>
	{{/if}}
	<h2>{{$title}}</h2>
	<div class="clear"></div>
</div>
{{$upload_form}}
<div id="photo-album-contents" class="generic-content-wrapper">
	{{foreach $photos as $photo}}
		{{include file="photo_top.tpl"}}
	{{/foreach}}
	<div id="page-end"></div>
</div>
<div class="photos-end"></div>
<script>$(document).ready(function() { loadingPage = false; justifyPhotos(); });</script>
<div id="page-spinner"></div>
