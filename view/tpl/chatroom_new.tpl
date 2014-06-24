<h1>{{$header}}</h1>

<form action="chat" method="post" >
{{include file="field_input.tpl" field=$name}}
<button class="btn btn-default btn-xs" data-toggle="modal" data-target="#aclModal" onclick="return false;">{{$permissions}}</button>
{{$acl}}
<div class="clear"></div>
<br />
<br />
<input type="submit" name="submit" value="{{$submit}}" />
</form>


