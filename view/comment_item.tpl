

<div class="comment-$wwedit-wrapper" id="comment-edit-wrapper-$id" style="display: block;">
	<form class="comment-edit-form" id="comment-edit-form-$id" action="item" method="post" >
		<input type="hidden" name="type" value="$type" />
		<input type="hidden" name="profile_uid" value="$profile_uid" />
		<input type="hidden" name="parent" value="$parent" />
		<input type="hidden" name="return" value="$return_path" />

		<textarea id="comment-edit-text-$id" class="comment-edit-text-empty" name="body" onFocus="commentOpen(this,$id);" onBlur="commentClose(this,$id);" >Comment</textarea>


		<div class="comment-edit-submit-wrapper" id="comment-edit-submit-wrapper-$id" style="display: none;" >
			<input type="submit" id="comment-edit-submit-$id" class="comment-edit-submit" name="submit" value="Submit" />
		</div>

		<div id="comment-edit-end"></div>
	</form>
</div>
