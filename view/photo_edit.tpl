
<form action="photos/$resource_id" method="post" id="photo_edit_form" >

	<input type="hidden" name="item_id" value="$item_id" />

	<label id="photo-edit-caption-label" for="photo-edit-caption">$capt_label</label>
	<input id="photo-edit-caption" type="text" size="84" name="desc" value="$caption" />

	<div id="photo-edit-caption-end"></div>

	<label id="photo-edit-tags-label" for="photo-edit-tags-textarea" >$tag_label</label>
	<textarea name="tags" id="photo-edit-tags-textarea" rows="3" cols="64" >$tags</textarea>
	<div id="photo-edit-tags-end"></div>

	<input id="photo-edit-submit-button" type="submit" name="submit" value="$submit" />
	<input id="photo-edit-delete-button" type="submit" name="delete" value="$delete" onclick="return confirmDelete()"; />

	<div id="photo-edit-end"></div>
</form>
