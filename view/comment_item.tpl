
<div class="comment-edit" id="comment-edit-$id" onclick="openClose('comment-edit-wrapper-$id');" >Comments</div>
<div class="comment-edit-wrapper" id="comment-edit-wrapper-$id" style="display: none;">
	<form class="comment-edit-form" id="comment-edit-form-$id" action="item" method="post" >
		<input type="hidden" name="type" value="jot" />
		<input type="hidden" name="profile_uid" value="$profile_uid" />
		<input type="hidden" name="parent" value="$parent" />
		<textarea rows="3" cols="40" id="comment-edit-text-$id" name="body" ></textarea>


		<div id="comment-edit-submit-wrapper" >
			<input type="submit" id="comment-edit-submit" name="submit" value="Submit" />
		</div>
		<div id="comment-edit-end"></div>
	</form>
</div>
