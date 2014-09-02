<h3>{{$header}}</h3>

<form action="admin/profs" method="post" >

{{if $id}}
<input type="hidden" name="id" value="{{$id}}" />
{{/if}}

{{include file="field_input.tpl" field=$field_name}}
{{include file="field_input.tpl" field=$field_type}}
{{include file="field_input.tpl" field=$field_desc}}
{{include file="field_input.tpl" field=$field_help}}

<input name="submit" type="submit" value="{{$submit}}" />

</form>
