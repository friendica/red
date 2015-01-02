<div class="generic-content-wrapper-styled">
<h1>{{$ptitle}}</h1>

{{$nickname_block}}

<form action="settings" id="settings-form" method="post" autocomplete="off" >
<input type='hidden' name='form_security_token' value='{{$form_security_token}}' />

<h3 class="settings-heading">{{$h_basic}}</h3>

{{include file="field_input.tpl" field=$username}}
{{include file="field_select_grouped.tpl" field=$timezone}}
{{include file="field_input.tpl" field=$defloc}}
{{include file="field_checkbox.tpl" field=$allowloc}}

{{include file="field_checkbox.tpl" field=$adult}}

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"{{if !$expert}} onclick="$('select').prop('disabled', false);"{{/if}} />
</div>


<h3 class="settings-heading">{{$h_prv}}</h3>

{{include file="field_select_grouped.tpl" field=$role}}

<div id="advanced-perm" style="display:{{if $permissions_set}}none{{else}}block{{/if}};">
{{include file="field_checkbox.tpl" field=$hide_presence}}

<button type="button" class="btn btn-default" data-toggle="collapse" data-target="#settings-permissions-wrapper">{{$lbl_p2macro}}</button>

<div class="collapse well" id="settings-permissions-wrapper">

{{foreach $permiss_arr as $permit}}
	{{include file="field_select.tpl" field=$permit}}
{{/foreach}}
	<div class="settings-submit-wrapper" >
	<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
	</div>
</div>

<div id="settings-default-perms" class="settings-default-perms" >
	<button class="btn btn-default" data-toggle="modal" data-target="#aclModal" onclick="return false;">{{$permissions}}</button>
	{{$aclselect}}
	<div id="settings-default-perms-menu-end"></div>
</div>
<br/>
<div id="settings-default-perms-end"></div>

{{$group_select}}

{{$profile_in_dir}}

</div>

<div class="settings-common-perms">



{{$suggestme}}

{{include file="field_checkbox.tpl" field=$blocktags}}


{{include file="field_input.tpl" field=$expire}}

</div>



<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"{{if !$expert}} onclick="$('select').prop('disabled', false);"{{/if}} />
</div>



<h3 class="settings-heading">{{$h_not}}</h3>
<div id="settings-notifications">

<div id="settings-activity-desc">{{$activity_options}}</div>
{{*not yet implemented *}}
{{*include file="field_checkbox.tpl" field=$post_joingroup*}}
{{include file="field_checkbox.tpl" field=$post_newfriend}}
{{include file="field_checkbox.tpl" field=$post_profilechange}}


<div id="settings-notify-desc">{{$lbl_not}}</div>

<div class="group">
{{include file="field_intcheckbox.tpl" field=$notify1}}
{{include file="field_intcheckbox.tpl" field=$notify2}}
{{include file="field_intcheckbox.tpl" field=$notify3}}
{{include file="field_intcheckbox.tpl" field=$notify4}}
{{include file="field_intcheckbox.tpl" field=$notify5}}
{{include file="field_intcheckbox.tpl" field=$notify6}}
{{include file="field_intcheckbox.tpl" field=$notify7}}
{{include file="field_intcheckbox.tpl" field=$notify8}}
</div>

<div id="settings-vnotify-desc">{{$lbl_vnot}}</div>

<div class="group">
{{include file="field_intcheckbox.tpl" field=$vnotify1}}
{{include file="field_intcheckbox.tpl" field=$vnotify2}}
{{include file="field_intcheckbox.tpl" field=$vnotify3}}
{{include file="field_intcheckbox.tpl" field=$vnotify4}}
{{include file="field_intcheckbox.tpl" field=$vnotify5}}
{{include file="field_intcheckbox.tpl" field=$vnotify6}}
{{include file="field_intcheckbox.tpl" field=$vnotify10}}
{{include file="field_intcheckbox.tpl" field=$vnotify7}}
{{include file="field_intcheckbox.tpl" field=$vnotify8}}
{{include file="field_intcheckbox.tpl" field=$vnotify9}}
{{include file="field_intcheckbox.tpl" field=$vnotify11}}
{{include file="field_intcheckbox.tpl" field=$always_show_in_notices}}

{{*include file="field_intcheckbox.tpl" field=$vnotify11*}}
</div>

{{include file="field_input.tpl" field=$evdays}}



</div>

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"{{if !$expert}} onclick="$('select').prop('disabled', false);"{{/if}} />
</div>


{{if $menus}}
<h3 class="settings-heading">{{$lbl_misc}}</h3>



<div id="settings-menu-desc">{{$menu_desc}}</div>
<div class="settings-channel-menu-div">
<select name="channel_menu" class="settings-channel-menu-sel">
{{foreach $menus as $menu }}
<option value="{{$menu.name}}" {{$menu.selected}} >{{$menu.name}} </option>
{{/foreach}}
</select>
</div>
<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"{{if !$expert}} onclick="$('select').prop('disabled', false);"{{/if}} />
</div>
<div id="settings-channel-menu-end"></div>
{{/if}}
<div id="settings-remove-account-link">
<button title="{{$permanent}}" class="btn btn-danger" type="submit" formaction="removeme">{{$removeme}}</button>
</div>

</div>
