<a href="{{$photo.link}}" id="photo-top-photo-link-{{$photo.id}}" title="{{$photo.title}}">
	<img src="{{$photo.src}}" alt="{{if $photo.album.name}}{{$photo.album.name}}{{elseif $photo.desc}}{{$photo.desc}}{{else}}{{$photo.alt}}{{/if}}" title="{{$photo.title}}" id="photo-top-photo-{{$photo.id}}" />
</a>

