
<h2>{{$header}}</h2>

<form id="mitemedit" action="mitem/{{$menu_id}}{{if $mitem_id}}/{{$mitem_id}}{{/if}}" method="post" >

<input type="hidden" name="menu_id" value="{{$menu_id}}" />

{{if $mitem_id}}
<input type="hidden" name="mitem_id" value="{{$mitem_id}}" />
{{/if}}

{{include file="field_input.tpl" field=$mitem_desc}} 
{{include file="field_input.tpl" field=$mitem_link}}
{{include file="field_input.tpl" field=$mitem_order}}
{{include file="field_checkbox.tpl" field=$usezid}}
{{include file="field_checkbox.tpl" field=$newwin}}

<div id="settings-default-perms" class="settings-default-perms" >
	<button class="btn btn-default btn-xs" data-toggle="modal" data-target="#aclModal" onclick="return false;">{{$permissions}}</button>
	{{$aclselect}}
	<div id="settings-default-perms-menu-end"></div>
</div>
<br/>
<div id="settings-default-perms-end"></div>



<div class="mitemedit-submit-wrapper" >
<input type="submit" name="submit" class="mitemedit-submit" value="{{$submit}}" />
</div>

</form>
