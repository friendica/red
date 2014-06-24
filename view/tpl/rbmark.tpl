<h3>{{$header}}</h3>


<form action="rbmark" method="post" >

<input type="hidden" name="private" value="{{$private}}" />
<input type="hidden" name="ischat" value="{{$ischat}}" />

{{include file="field_input.tpl" field=$url}}
{{include file="field_input.tpl" field=$title}}
{{include file="field_select.tpl" field=$menus}}
{{include file="field_input.tpl" field=$menu_name}}

<input type="submit" name="submit" value="{{$submit}}" />

</form>
