<div id="live-photos"></div>
<div class="generic-content-wrapper">

	<div class="section-title-wrapper">

		<div class="btn-group btn-group-xs pull-right">
			{{if $prevlink}}
			<a href="{{$prevlink.0}}" class="btn btn-default" title="{{$prevlink.1}}"><i class="icon-backward"></i></a>
			{{/if}}
			{{if $nextlink}}
			<a href="{{$nextlink.0}}" class="btn btn-default" title="{{$nextlink.1}}"><i class="icon-forward"></i></a>
			{{/if}}
		</div>
		<div class="btn-group btn-group-xs pull-right dropdown">
			{{if $tools}}
			<a  class="btn btn-default" title="{{$tools.profile.1}}" href="{{$tools.profile.0}}"><i class="icon-user"></i></a>
			{{/if}}

			{{if $edit}}
			<i class="icon-pencil btn btn-default" title="{{$edit.edit}}" onclick="openClose('photo-edit-edit');"></i>
			{{/if}}

			{{if $lock}}
			<i class="icon-lock btn btn-default dropdown-toggle" data-toggle="dropdown" title="{{$lock}}" onclick="lockview(event,{{$id}});" ></i><ul id="panel-{{$id}}" class="lockview-panel dropdown-menu"></ul>
			{{/if}}
			&nbsp;
		</div>

		<h2>{{if $desc}}{{$desc}}{{elseif $filename}}{{$filename}}{{else}}{{$unknown}}{{/if}}</h2>

		<div class="clear"></div>

	</div>
	<div id="photo-edit-edit" style="display: none;">
		<form action="photos/{{$edit.nickname}}/{{$edit.resource_id}}" method="post" id="photo_edit_form">
			<input type="hidden" name="item_id" value="{{$edit.item_id}}">
			<label id="photo-edit-albumname-label" for="photo-edit-albumname">{{$edit.newalbum}}</label>
			<input id="photo-edit-albumname" type="text" name="albname" value="{{$edit.album}}" list="dl-albums">
			{{if $edit.albums}}
			<datalist id="dl-albums">
			{{foreach $edit.albums as $al}}
				{{if $al.text}}
				<option value="{{$al.text}}">
				{{/if}}
			{{/foreach}}
			</datalist>
			{{/if}}
			<div id="photo-edit-albumname-end"></div>
				<label id="photo-edit-caption-label" for="photo-edit-caption">{{$edit.capt_label}}</label>
			<input id="photo-edit-caption" type="text" name="desc" value="{{$edit.caption}}">
				<div id="photo-edit-caption-end"></div>
				<label id="photo-edit-tags-label" for="photo-edit-newtag" >{{$edit.tag_label}}</label>
			<input name="newtag" id="photo-edit-newtag" title="{{$edit.help_tags}}" type="text">
				<div id="photo-edit-tags-end"></div>
			<div id="photo-edit-rotate-wrapper">
				<div id="photo-edit-rotate-label">
					{{$edit.rotatecw}}<br>
					{{$edit.rotateccw}}
				</div>
				<input type="radio" name="rotate" value="1"><br>
				<input type="radio" name="rotate" value="2">
			</div>
			<div id="photo-edit-rotate-end"></div>
				<div id="settings-default-perms" class="settings-default-perms">
				<span id="jot-perms-icon" class="{{$edit.lockstate}}"></span>
				<button class="btn btn-default btn-xs" data-toggle="modal" data-target="#aclModal" onclick="return false;">{{$edit.permissions}}</button>
				{{$edit.aclselect}}
				<div id="settings-default-perms-menu-end"></div>
			</div>
			<br/>
			<div id="settings-default-perms-end"></div>
				<input id="photo-edit-submit-button" type="submit" name="submit" value="{{$edit.submit}}">
			<input id="photo-edit-delete-button" type="submit" name="delete" value="{{$edit.delete}}" onclick="return confirmDelete();">
				<div id="photo-edit-end"></div>
		</form>
	</div>

	<div id="photo-view-wrapper">


		<div id="photo-photo"><a href="{{$photo.href}}" title="{{$photo.title}}" onclick="$.colorbox({href: '{{$photo.href}}'}); return false;"><img style="width: 100%;" src="{{$photo.src}}"></a></div>
		<div id="photo-photo-end"></div>

		{{if $tags}}
		<div class="photo-item-tools-left" id="in-this-photo">
			<span id="in-this-photo-text">{{$tag_hdr}}</span>
			{{foreach $tags as $t}}
				{{$t.0}}{{if $edit}}<span id="tag-remove">&nbsp;<a href="{{$t.1}}"><i class="icon-remove"></i></a>&nbsp;</span>{{/if}}
			{{/foreach}}
		</div>
		{{/if}}

		<div class="photo-item-tools">
			{{if $like_count ||  $dislike_count}}
			<div class="photo-item-tools-left pull-left">
					<div class="{{if $like_count &&  $dislike_count}}btn-group{{/if}}">
						{{if $like_count}}
						<div class="btn-group">
							<button type="button" class="btn btn-default btn-sm wall-item-like dropdown-toggle" data-toggle="dropdown" id="wall-item-like-{{$id}}">{{$like_count}} {{$like_button_label}}</button>
							{{if $like_list_part}}
							<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-like-{{$id}}">{{foreach $like_list_part as $liker}}<li role="presentation">{{$liker}}</li>{{/foreach}}</ul>
							{{else}}
							<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-like-{{$id}}">{{foreach $like_list as $liker}}<li role="presentation">{{$liker}}</li>{{/foreach}}</ul>
							{{/if}}
						</div>
						{{/if}}
						{{if $dislike_count}}
						<div class="btn-group">
							<button type="button" class="btn btn-default btn-sm wall-item-dislike dropdown-toggle" data-toggle="dropdown" id="wall-item-dislike-{{$id}}">{{$dislike_count}} {{$dislike_button_label}}</button>
							{{if $dislike_list_part}}
							<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-dislike-{{$id}}">{{foreach $dislike_list_part as $disliker}}<li role="presentation">{{$disliker}}</li>{{/foreach}}</ul>
							{{else}}
							<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-dislike-{{$id}}">{{foreach $dislike_list as $disliker}}<li role="presentation">{{$disliker}}</li>{{/foreach}}</ul>
							{{/if}}
						</div>
						{{/if}}
					</div>
					{{if $like_list_part}}
					<div class="modal" id="likeModal-{{$id}}">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
									<h4 class="modal-title">{{$like_modal_title}}</h4>
								</div>
								<div class="modal-body">
									<ul>{{foreach $like_list as $liker}}<li role="presentation">{{$liker}}</li>{{/foreach}}</ul>
								</div>
								<div class="modal-footer clear">
									<button type="button" class="btn btn-default" data-dismiss="modal">{{$modal_dismiss}}</button>
								</div>
							</div><!-- /.modal-content -->
						</div><!-- /.modal-dialog -->
					</div><!-- /.modal -->
					{{/if}}
					{{if $dislike_list_part}}
					<div class="modal" id="dislikeModal-{{$id}}">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
									<h4 class="modal-title">{{$dislike_modal_title}}</h4>
								</div>
								<div class="modal-body">
									<ul>{{foreach $dislike_list as $disliker}}<li role="presentation">{{$disliker}}</li>{{/foreach}}</ul>
								</div>
								<div class="modal-footer clear">
									<button type="button" class="btn btn-default" data-dismiss="modal">{{$modal_dismiss}}</button>
								</div>
							</div><!-- /.modal-content -->
						</div><!-- /.modal-dialog -->
					</div><!-- /.modal -->
					{{/if}}
				</div>
			</div>
			{{/if}}


			{{if $likebuttons}}
			<div class="photo-item-tools-right btn-group pull-right">
				<button type="button" class="btn btn-default btn-sm" onclick="dolike({{$id}},'like'); return false">
					<i class="icon-thumbs-up-alt" title="{{$likethis}}"></i>
				</button>
				<button type="button" class="btn btn-default btn-sm" onclick="dolike({{$id}},'dislike'); return false">
					<i class="icon-thumbs-down-alt" title="{{$nolike}}"></i>
				</button>
			</div>
			<div id="like-rotator-{{$id}}" class="like-rotator pull-right"></div>
			{{/if}}
		<div class="clear"></div>
		</div>



</div>

{{$comments}}

{{if $commentbox}}
<div class="wall-item-comment-wrapper{{if $comments}} wall-item-comment-wrapper-wc{{/if}}" >
	{{$commentbox}}
</div>
{{/if}}


<div class="clear"></div>

</div>

{{$paginate}}

