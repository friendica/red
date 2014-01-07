
<h1>{{$header}}</h1>

<h2>{{$file.filename}}</h2>


<div id="attach-edit-backlink"><a href="filestorage/{{$channelnick}}">{{$backlink}}</a></div>


<form action="filestorage/{{$channelnick}}/{{$file.id}}/edit" method="post" >

<input type="hidden" name="channelnick" value="{{$channelnick}}" />
<input type="hidden" name="filehash" value="{{$file.hash}}" />
<input type="hidden" name="uid" value="{{$uid}}" />
<input type="hidden" name="fileid" value="{{$file.id}}" />

{{if $isadir}}
<div id="attach-edit-recurse" >
  <label id="attach-edit-recurse-text" for="attach-recurse-input" >{{$recurse}}</label>
  <input id="attach-recurse-input" type="checkbox" name="recurse" value="1" />
</div>
{{/if}}

<div id="attach-edit-perms" >
{{$aclselect}}
</div>

<div class="clear"></div>
<input type="submit" name="submit" value="{{$submit}}" />
</form>


