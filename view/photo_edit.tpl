
<form action="photos" method="post" id="photo_edit_form" >

	<label id="photo-edit-caption-label" for="photo-edit-caption">$capt_label</label>
	<input type="text size="64" name="desc" value="$caption" />

	<label id="photo-edit-tags-label" for="photo-edit-tags-textarea" >$tag_label</label>
	<textarea name="tags" id="photo-edit-tags-textarea">$tags</textarea>

	<input type="submit" name="submit" value="$submit" />
</form>
