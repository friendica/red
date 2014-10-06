<div class="section-title-wrapper">
	<div class="btn-group btn-group-xs pull-right">
		{{if $edit}}
		<a class="btn btn-default" href="{{$edit.1}}" title="{{$edit.0}}"><i class="icon-pencil"></i></a>
		{{/if}}
		<a class="btn btn-default" href="{{$order.1}}" title="{{$order.0}}"><i class="icon-sort"></i></a>
		{{if $can_post}}
		<a class="btn btn-xs btn-success" href="{{$upload.1}}"><i class="icon-upload"></i>&nbsp;{{$upload.0}}</a>
		{{/if}}
	</div>
	<h2>{{$album}}</h2>

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
