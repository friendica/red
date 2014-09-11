<!-- -->
<div id="photo-album-contents-{{$page}}">
{{foreach $photos as $photo}}
	{{include file="photo_top.tpl"}}
{{/foreach}}
</div>
<script>justifyPhotos({{$page}});</script>
