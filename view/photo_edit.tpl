
<form action="photos/$id" method="post" id="photo_edit_form" >

	<input type="hidden" name="item_id" value="$item_id" />

	<label id="photo-edit-caption-label" for="photo-edit-caption">$capt_label</label>
	<input id="photo-edit-caption" type="text" size="64" name="desc" value="$caption" />

	<div id="photo-edit-caption-end"></div>

	<label id="photo-edit-tags-label" for="photo-edit-tags-textarea" >$tag_label</label>
	<textarea name="tags" id="photo-edit-tags-textarea">$tags</textarea>
	<div id="photo-edit-tags-end"></div>

	<input type="submit" name="submit" value="$submit" />
	<div id="photo-edit-end"></div>
</form>
