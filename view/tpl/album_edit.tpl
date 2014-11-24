<div id="photo-album-edit-wrapper" class="section-content-tools-wrapper">
	<form name="photo-album-edit-form" id="photo-album-edit-form" action="photos/{{$nickname}}/album/{{$hexalbum}}" method="post" >
		<div class="form-group">
			<label id="photo-album-edit-name-label" for="photo-album-edit-name">{{$nametext}}</label>
			<input type="text" name="albumname" placeholder="{{$name_placeholder}}" value="{{$album}}" class="form-control" list="dl-album-edit" />
			<datalist id="dl-album-edit">
			{{foreach $albums as $al}}
				{{if $al.text}}
				<option value="{{$al.text}}">
				{{/if}}
			{{/foreach}}
			</datalist>
		</div>
		<div class="form-group">
			<button id="photo-album-edit-submit" type="submit" name="submit" class="btn btn-primary btn-sm pull-right" />{{$submit}}</button>
			<button id="photo-album-edit-drop" type="submit" name="dropalbum" value="{{$dropsubmit}}" class="btn btn-danger btn-sm pull-left" onclick="return confirmDelete();" />{{$dropsubmit}}</button>
		</div>
	</form>
	<div id="photo-album-edit-end" class="clear"></div>
</div>


