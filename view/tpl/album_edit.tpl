<div id="photo-album-edit-wrapper" class="section-content-tools-wrapper">
<form name="photo-album-edit-form" id="photo-album-edit-form" action="photos/{{$nickname}}/album/{{$hexalbum}}" method="post" >


<label id="photo-album-edit-name-label" for="photo-album-edit-name" >{{$nametext}}</label>
<input type="text" name="albumname" value="{{$album}}" list="dl-album-edit" />
	<datalist id="dl-album-edit">
	{{foreach $albums as $al}}
		{{if $al.text}}
		<option value="{{$al.text}}">
		{{/if}}
	{{/foreach}}
	</datalist>

<div id="photo-album-edit-name-end"></div>

<input id="photo-album-edit-submit" type="submit" name="submit" value="{{$submit}}" />
<input id="photo-album-edit-drop" type="submit" name="dropalbum" value="{{$dropsubmit}}" onclick="return confirmDelete();" />

</form>
</div>
<div id="photo-album-edit-end" ></div>
