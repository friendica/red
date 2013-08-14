
<h2>{{$header}}</h2>

{{if $menu_id}}
<a href="medit/{{$menu_id}}" title="{{$hintedit}}">{{$editcontents}}</a>
{{/if}}

<form id="menuedit" action="menu{{if $menu_id}}/{{$menu_id}}{{/if}}" method="post" >

{{if $menu_id}}
<input type="hidden" name="menu_id" value="{{$menu_id}}" />
{{/if}}

{{include file="field_input.tpl" field=$menu_name}} 
{{include file="field_input.tpl" field=$menu_desc}}

<div class="menuedit-submit-wrapper" >
<input type="submit" name="submit" class="menuedit-submit" value="{{$submit}}" />
</div>

</form>
