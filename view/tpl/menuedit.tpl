
<h2>{{$header}}</h2>

{{if $menu_id}}
<a href="mitem/{{$menu_id}}" title="{{$hintedit}}">{{$editcontents}}</a>
{{/if}}

<form id="menuedit" action="menu{{if $menu_id}}/{{$menu_id}}{{/if}}" method="post" >

{{if $menu_id}}
<input type="hidden" name="menu_id" value="{{$menu_id}}" />
{{/if}}
{{if $menu_system}}
<input type="hidden" name="menu_system" value="{{$menu_system}}" />
{{/if}}


{{include file="field_input.tpl" field=$menu_name}} 
{{include file="field_input.tpl" field=$menu_desc}}
{{include file="field_checkbox.tpl" field=$menu_bookmark}}
<div class="menuedit-submit-wrapper" >
<input type="submit" name="submit" class="menuedit-submit" value="{{$submit}}" />
</div>

</form>
