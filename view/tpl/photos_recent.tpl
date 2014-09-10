<h3>{{$title}}</h3>
{{if $can_post}}
<a id="photo-top-upload-link" href="{{$upload.1}}">{{$upload.0}}</a>
{{/if}}

<div id="photo-album-contents">
{{foreach $photos as $photo}}
	{{include file="photo_top.tpl"}}
{{/foreach}}
<div id="page-end"></div>
</div>
<div class="photos-end"></div>
<script>$(document).ready(function() { loadingPage = false;});</script>
<div id="page-spinner"></div>
