<div class="wall-item-like-buttons" id="wall-item-like-buttons-{{$id}}">
	<i class="icon-thumbs-up-alt item-tool" title="{{$likethis}}" onclick="dolike({{$id}},'like'); return false"></i>
	<i class="icon-thumbs-down-alt item-tool" title="{{$nolike}}" onclick="dolike({{$id}},'dislike'); return false"></i>
	<div id="like-rotator-{{$id}}" class="like-rotator"></div>
</div>
