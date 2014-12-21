<form action="filestorage/{{$channelnick}}/{{$file.id}}/edit" method="post" >

<div id="attach-edit-perms" >
<button id="dbtn-acl" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" title="{{$permset}}" onclick="return false;">
	<i id="jot-perms-icon" class="icon-{{$lockstate}} jot-icons"></i>
</button>
<button id="dbtn-submit" class="btn btn-primary btn-sm" type="submit" name="submit">
	{{$submit}}
</button>
</div>

{{$aclselect}}

<input type="hidden" name="channelnick" value="{{$channelnick}}" />
<input type="hidden" name="filehash" value="{{$file.hash}}" />
<input type="hidden" name="uid" value="{{$uid}}" />
<input type="hidden" name="fileid" value="{{$file.id}}" />

{{if $isadir}}
<div id="attach-edit-recurse" >
  <label id="attach-edit-recurse-text" for="attach-recurse-input" >{{$recurse}}</label>
  <input id="attach-recurse-input" type="checkbox" name="recurse" value="1" />
</div>
{{else}}
<div class="cut-paste-desc">{{$cpdesc}}</div>
<input type="text" id="cutpasteinput" name="cutpastelink" value="[attachment]{{$file.hash}},{{$file.revision}}[/attachment]" onclick="this.select();" /><br />
{{/if}}

<div class="cut-paste-desc">{{$cpldesc}}</div>
<input type="text" id="linkpasteinput" name="cutpasteextlink" value="{{$cloudpath}}" onclick="this.select();"/><br />
<div class="clear"></div>

</form>

