<form action="filestorage/{{$channelnick}}/{{$file.id}}/edit" method="post" >
	<input type="hidden" name="channelnick" value="{{$channelnick}}" />
	<input type="hidden" name="filehash" value="{{$file.hash}}" />
	<input type="hidden" name="uid" value="{{$uid}}" />
	<input type="hidden" name="fileid" value="{{$file.id}}" />

	<div id="attach-edit-tools-share" class="btn-group form-group">
		{{if !$isadir}}
		<a href="/rpost?body=[attachment]{{$file.hash}},{{$file.revision}}[/attachment]" id="attach-btn" class="btn btn-default btn-xs" title="{{$attach_btn_title}}">
			<i class="icon-paperclip jot-icons"></i>
		</a>
		{{/if}}
		<button id="link-btn" class="btn btn-default btn-xs" type="button" onclick="openClose('link-code');" title="{{$link_btn_title}}">
			<i class="icon-share jot-icons"></i>
		</button>
	</div>
	<div id="attach-edit-tools-perms" class="form-group pull-right btn-group">
		{{if $isadir}}
		<div id="attach-edit-perms-recurse" class="btn-group" data-toggle="buttons">
			<label class="btn btn-default btn-xs" title="{{$recurse}}">
				<input type="checkbox" autocomplete="off" name="recurse" value="1"><i class="icon-level-down jot-icons"></i>
			</label>
		</div>
		{{/if}}
		{{if !$isadir}}
		<div id="attach-edit-activity" class="btn-group" data-toggle="buttons">
			<label class="btn btn-default btn-xs" title="{{$activity_btn_title}}">
				<input type="checkbox" autocomplete="off" name="no_activity" value="1"><i class="icon-ban-circle jot-icons"></i>
			</label>
		</div>
		{{/if}}
		<div id="attach-edit-perms" class="btn-group">
			<button id="dbtn-acl" class="btn btn-default btn-xs" data-toggle="modal" data-target="#aclModal" title="{{$permset}}" onclick="return false;">
				<i id="jot-perms-icon" class="icon-{{$lockstate}} jot-icons"></i>
			</button>
			<button id="dbtn-submit" class="btn btn-primary btn-xs" type="submit" name="submit">
				{{$submit}}
			</button>
		</div>
	</div>

	{{$aclselect}}

	<div id="link-code" class="form-group">
		<label for="">{{$cpldesc}}</label>
		<input type="text" class="form-control" id="linkpasteinput" name="cutpasteextlink" value="{{$cloudpath}}" onclick="this.select();"/>
	</div>
</form>

