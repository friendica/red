<div class="generic-content-wrapper">
<h1>{{$ptitle}}</h1>

{{$nickname_block}}

<form action="settings" id="settings-form" method="post" autocomplete="off" >
<input type='hidden' name='form_security_token' value='{{$form_security_token}}' />

<h3 class="settings-heading">{{$h_basic}}</h3>

{{include file="field_input.tpl" field=$username}}
{{include file="field_custom.tpl" field=$timezone}}
{{include file="field_input.tpl" field=$defloc}}
{{include file="field_checkbox.tpl" field=$allowloc}}

{{include file="field_checkbox.tpl" field=$adult}}

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"{{if !$expert}} onclick="$('select').prop('disabled', false);"{{/if}} />
</div>


<h3 class="settings-heading">{{$h_prv}}</h3>

{{include file="field_checkbox.tpl" field=$hide_presence}}


<h3 id="settings-privacy-macros">{{$lbl_pmacro}}</h3>
<ul>
<li><a href="#" onclick="channel_privacy_macro(2); return false" id="settings_pmacro2">{{$pmacro2}}</a></li>
<li><a href="#" onclick="channel_privacy_macro(1); return false" id="settings_pmacro1">{{$pmacro1}}</a></li>
<li><a href="#" onclick="channel_privacy_macro(3); return false" id="settings_pmacro3">{{$pmacro3}}</a></li>
<li><a href="#" onclick="channel_privacy_macro(0); return false" id="settings_pmacro0">{{$pmacro0}}</a></li>
</ul>


<button type="button" class="btn btn-xs btn-warning" data-toggle="collapse" data-target="#settings-permissions-wrapper">{{$lbl_p2macro}}</button>



<div class="collapse well" id="settings-permissions-wrapper">
{{if !$expert}}
	<div class="alert alert-info">{{$hint}}</div>
{{/if}}

{{foreach $permiss_arr as $permit}}
	{{if $expert}}
		{{include file="field_select.tpl" field=$permit}}
	{{else}}
		{{include file="field_select_disabled.tpl" field=$permit}}
	{{/if}}
{{/foreach}}

{{if $expert}}
	<div class="settings-submit-wrapper" >
	<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
	</div>
{{/if}}

</div>
<div class="settings-common-perms">
{{$profile_in_dir}}

{{$suggestme}}

{{include file="field_input.tpl" field=$maxreq}}

{{include file="field_input.tpl" field=$cntunkmail}}
</div>

<div id="settings-default-perms" class="settings-default-perms" >
	<a href="#profile-jot-acl-wrapper" id="settings-default-perms-menu" >{{$permissions}} {{$permdesc}}</a>
	<div id="settings-default-perms-menu-end"></div>

	<div id="settings-default-perms-select" style="display: none; margin-bottom: 20px" >
	
	<div style="display: none;">
		<div id="profile-jot-acl-wrapper" style="width:auto;height:auto;overflow:auto;">
			{{$aclselect}}
		</div>
	</div>

	</div>
</div>
<br/>
<div id="settings-default-perms-end"></div>

{{$group_select}}


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"{{if !$expert}} onclick="$('select').prop('disabled', false);"{{/if}} />
</div>



<h3 class="settings-heading">{{$h_not}}</h3>
<div id="settings-notifications">

<div id="settings-activity-desc">{{$activity_options}}</div>
{{*the next two aren't yet implemented *}}
{{*include file="field_checkbox.tpl" field=$post_newfriend*}}
{{*include file="field_checkbox.tpl" field=$post_joingroup*}}
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

</div>

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"{{if !$expert}} onclick="$('select').prop('disabled', false);"{{/if}} />
</div>

</div>
