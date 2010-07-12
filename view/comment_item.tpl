
<div class="comment-edit" id="comment-edit-$id" onclick="openClose('comment-edit-wrapper-$id');" >Comments</div>
<div class="comment-edit-wrapper" id="comment-edit-wrapper-$id" style="display: block;">
	<form class="comment-edit-form" id="comment-edit-form-$id" action="item" method="post" >
		<input type="hidden" name="type" value="jot" />
		<input type="hidden" name="profile_uid" value="$profile_uid" />
		<input type="hidden" name="parent" value="$parent" />
		<textarea rows="2" cols="24" id="comment-edit-text-$id" name="body" onFocus="this.rows=5; this.cols=40; openMenu('comment-edit-submit-$id');" onBlur="this.rows=2; this.cols=24; closeMenu('comment-edit-submit-$id'); this.value='';"></textarea>


		<div class="comment-edit-submit-wrapper" id="comment-edit-submit-$id" style="display: none;" >
			<input type="submit" id="comment-edit-submit" name="submit" value="Submit" />
		</div>
		<div id="comment-edit-end"></div>
	</form>
</div>
