<div class="wall-item-like-buttons" id="wall-item-like-buttons-{{$id}}">
	<a href="#" class="icon like" title="{{$likethis}}" onclick="dolike({{$id}},'like'); return false"></a>
	<a href="#" class="icon dislike" title="{{$nolike}}" onclick="dolike({{$id}},'dislike'); return false"></a>
	<div id="like-rotator-{{$id}}" class="like-rotator"></div>
</div>
