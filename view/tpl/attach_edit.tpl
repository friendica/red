<form action="filestorage/{{$channelnick}}/{{$file.id}}/edit" method="post" >

	<div id="attach-edit-tools" class="btn-group form-group">
		{{if !$isadir}}
		<a href="/rpost?body=[attachment]{{$file.hash}},{{$file.revision}}[/attachment]" id="attach-btn" class="btn btn-default btn-xs">
			<i class="icon-paperclip jot-icons"></i>
		</a>
		{{/if}}
		<button id="link-btn" class="btn btn-default btn-xs" type="button" onclick="openClose('link-code');">
			<i class="icon-share jot-icons"></i>
		</button>
	</div>
	<div id="attach-edit-perms" class="btn-group form-group pull-right">
		<button id="dbtn-acl" class="btn btn-default btn-xs" data-toggle="modal" data-target="#aclModal" title="{{$permset}}" onclick="return false;">
			<i id="jot-perms-icon" class="icon-{{$lockstate}} jot-icons"></i>
		</button>
		<button id="dbtn-submit" class="btn btn-primary btn-xs" type="submit" name="submit">
			{{$submit}}
		</button>
	</div>
	{{$aclselect}}

	<input type="hidden" name="channelnick" value="{{$channelnick}}" />
	<input type="hidden" name="filehash" value="{{$file.hash}}" />
	<input type="hidden" name="uid" value="{{$uid}}" />
	<input type="hidden" name="fileid" value="{{$file.id}}" />

	{{if $isadir}}
	<div class="form-group">
		<label id="attach-edit-recurse-text" class="checkbox-inline" for="attach-recurse-input" >
			<input class="checkbox-inline" id="attach-recurse-input" type="checkbox" name="recurse" value="1" />{{$recurse}}
		</label>
	</div>
	{{/if}}

	<div id="link-code" class="form-group">
		<label for="">{{$cpldesc}}</label>
		<input type="text" class="form-control" id="linkpasteinput" name="cutpasteextlink" value="{{$cloudpath}}" onclick="this.select();"/>
	</div>
</form>

