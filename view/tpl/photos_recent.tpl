<div class="section-title-wrapper">
	{{if $can_post}}
	<a class="btn btn-xs btn-success pull-right" href="{{$upload.1}}"><i class="icon-upload"></i>&nbsp;{{$upload.0}}</a>
	{{/if}}
	<h2>{{$title}}</h2>
	<div class="clear"></div>
</div>
<div id="photo-album-contents">
	{{foreach $photos as $photo}}
		{{include file="photo_top.tpl"}}
	{{/foreach}}
	<div id="page-end"></div>
</div>
<div class="photos-end"></div>
<script>$(document).ready(function() { loadingPage = false; justifyPhotos(); });</script>
<div id="page-spinner"></div>
