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

		<div class="photo-item-tools">
			{{if $tags}}
			<div class="photo-item-tools-left pull-left">
				<div id="in-this-photo">
				<span id="in-this-photo-text">{{$tag_hdr}}</span>
				{{foreach $tags as $t}}
					{{$t.0}}{{if $edit}}<span id="tag-remove">&nbsp;<a href="{{$t.1}}"><i class="icon-remove"></i></a>&nbsp;</span>{{/if}}
				{{/foreach}}
				</div>
			</div>
			{{/if}}

			{{if $likebuttons}}
			<div class="photo-item-tools-right btn-group pull-right">
				<i class="icon-thumbs-up-alt item-tool btn btn-default btn-sm" title="{{$likethis}}" onclick="dolike({{$id}},'like'); return false"></i>
				<i class="icon-thumbs-down-alt item-tool btn btn-default btn-sm" title="{{$nolike}}" onclick="dolike({{$id}},'dislike'); return false"></i>
				{{$like}}
				{{$dislike}}
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

