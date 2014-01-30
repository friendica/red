<h1>{{$header}}</h1>

<form action="chat" method="post" >
{{include file="field_input.tpl" field=$name}}
<br />
<br />
{{$acl}}
<div class="clear"></div>
<input type="submit" name="submit" value="{{$submit}}" />
</form>


