<div class="section-title-wrapper">
	<div class="btn-group btn-group-xs pull-right">
		{{if $album_edit.1}}
		<i class="icon-pencil btn btn-default" title="{{$album_edit.0}}" onclick="openClose('photo-album-edit-wrapper');"></i>
		{{/if}}
		<a class="btn btn-default" href="{{$order.1}}" title="{{$order.0}}"><i class="icon-sort"></i></a>
		{{if $can_post}}
		<a class="btn btn-xs btn-success" href="{{$upload.1}}"><i class="icon-upload"></i>&nbsp;{{$upload.0}}</a>
		{{/if}}
	</div>
	<h2>{{$album}}</h2>

	<div class="clear"></div>
</div>
{{$album_edit.1}}
<div id="photo-album-contents" class="generic-content-wrapper">
	{{foreach $photos as $photo}}
		{{include file="photo_top.tpl"}}
	{{/foreach}}
	<div id="page-end"></div>
</div>
<div class="photos-end"></div>
<script>$(document).ready(function() { loadingPage = false; justifyPhotos(); });</script>
<div id="page-spinner"></div>
