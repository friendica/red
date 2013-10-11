{{include file="prettyphoto.tpl"}}

<div id="live-display"></div>
<h3><a href="{{$album.0}}">{{$album.1}}</a></h3>

<div id="photo-edit-link-wrap">
{{if $tools}}
<a id="photo-toprofile-link" href="{{$tools.profile.0}}">{{$tools.profile.1}}</a>
{{/if}}
{{if $lock}} | <i class="lockview icon-lock" title="{{$lock}}" onclick="lockview(event,'photo/{{$id}}');" ></i> {{/if}}
</div>

{{if $prevlink}}<div id="photo-prev-link"><a href="{{$prevlink.0}}"><i class="icon-backward photo-icons"></i></div>{{/if}}
<div id="photo-photo"><a href="{{$photo.href}}" title="{{$photo.title}}" rel="prettyPhoto"><img src="{{$photo.src}}" /></a></div>
{{if $nextlink}}<div id="photo-next-link"><a href="{{$nextlink.0}}"><i class="icon-forward photo-icons"></i></a></div>{{/if}}
<div id="photo-photo-end"></div>
<div id="photo-caption">{{$desc}}</div>
{{if $tags}}
<div id="in-this-photo-text">{{$tags.0}}</div>
<div id="in-this-photo">{{$tags.1}}</div>
{{/if}}
{{if $tags.2}}<div id="tag-remove"><a href="{{$tags.2}}">{{$tags.3}}</a></div>{{/if}}

{{if $edit}}
<div id="photo-edit-edit-wrapper" class="fakelink" onclick="openClose('photo-edit-edit');">{{$edit.edit}}</div>
<div id="photo-edit-edit" style="display: none;">
<form action="photos/{{$edit.nickname}}/{{$edit.resource_id}}" method="post" id="photo_edit_form" >

	<input type="hidden" name="item_id" value="{{$item_id}}" />

	<label id="photo-edit-albumname-label" for="photo-edit-albumname">{{$edit.newalbum}}</label>
	<input id="photo-edit-albumname" type="text" size="32" name="albname" value="{{$edit.album}}" />

	<div id="photo-edit-albumname-end"></div>

	<label id="photo-edit-caption-label" for="photo-edit-caption">{{$edit.capt_label}}</label>
	<input id="photo-edit-caption" type="text" size="84" name="desc" value="{{$edit.caption}}" />

	<div id="photo-edit-caption-end"></div>

	<label id="photo-edit-tags-label" for="photo-edit-newtag" >{{$edit.tag_label}}</label>
	<input name="newtag" id="photo-edit-newtag" size="84" title="{{$edit.help_tags}}" type="text" />

	<div id="photo-edit-tags-end"></div>
	<div id="photo-edit-rotate-wrapper">
		<div id="photo-edit-rotate-label">
			{{$edit.rotatecw}}<br>
			{{$edit.rotateccw}}
		</div>
		<input type="radio" name="rotate" value="1" /><br>
		<input type="radio" name="rotate" value="2" />
	</div>
	<div id="photo-edit-rotate-end"></div>

	<div id="photo-edit-perms" class="photo-edit-perms" >
		<a href="#photo-edit-perms-select" id="photo-edit-perms-menu" class="button popupbox" title="{{$edit.permissions}}"/>
			<span id="jot-perms-icon" class="icon {{$edit.lockstate}}" ></span>{{$edit.permissions}}
		</a>
		<div id="photo-edit-perms-menu-end"></div>
		
		<div style="display: none;">
			<div id="photo-edit-perms-select" >
				{{$edit.aclselect}}
			</div>
		</div>
	</div>
	<div id="photo-edit-perms-end"></div>

	<input id="photo-edit-submit-button" type="submit" name="submit" value="{{$edit.submit}}" />
	<input id="photo-edit-delete-button" type="submit" name="delete" value="{{$edit.delete}}" onclick="return confirmDelete()"; />

	<div id="photo-edit-end"></div>
</form>
</div>
{{/if}}

{{if $likebuttons}}
<div id="photo-like-div">
	{{$likebuttons}}
	{{$like}}
	{{$dislike}}	
</div>
{{/if}}

{{$comments}}

{{$paginate}}

