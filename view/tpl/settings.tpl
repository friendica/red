<h1>{{$ptitle}}</h1>

{{$nickname_block}}

<form action="settings" id="settings-form" method="post" autocomplete="off" >
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

<h3 class="settings-heading">{{$h_basic}}</h3>

{{include file="field_input.tpl" field=$username}}
{{include file="field_custom.tpl" field=$timezone}}
{{include file="field_input.tpl" field=$defloc}}
{{include file="field_checkbox.tpl" field=$allowloc}}


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>


<h3 class="settings-heading">{{$h_prv}}</h3>

<div id="settings-privacy-macros">{{$lbl_pmacro}}</div>
<ul>
<li><a href="#" onclick="channel_privacy_macro(3); return false" id="settings_pmacro3">{{$pmacro3}}</a></li>
<li><a href="#" onclick="channel_privacy_macro(2); return false" id="settings_pmacro2">{{$pmacro2}}</a></li>
<li><a href="#" onclick="channel_privacy_macro(1); return false" id="settings_pmacro1">{{$pmacro1}}</a></li>
<li><a href="#" onclick="channel_privacy_macro(0); return false" id="settings_pmacro0">{{$pmacro0}}</a></li>
</ul>



<div id="settings-permissions-wrapper">
{{foreach $permiss_arr as $permit}}
{{include file="field_select.tpl" field=$permit}}
{{/foreach}}
</div>


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>


{{$profile_in_dir}}

{{$suggestme}}

{{include file="field_input.tpl" field=$maxreq}}

{{include file="field_input.tpl" field=$cntunkmail}}


{{if $expireisfixed}}
{{* Remove above line when expire is working *}}
{{include file="field_input.tpl" field=$expire.days}}


<div class="field input">
	<span class="field_help"><a href="#advanced-expire-popup" id="advanced-expire" class='popupbox' title="{{$expire.advanced}}">{{$expire.label}}</a></span>
	<div style="display: none;">
		<div id="advanced-expire-popup" style="width:auto;height:auto;overflow:auto;">
			<h3>{{$expire.advanced}}</h3>
			{{include file="field_yesno.tpl" field=$expire.items}}
			{{include file="field_yesno.tpl" field=$expire.starred}}
			{{include file="field_yesno.tpl" field=$expire.network_only}}
		</div>
	</div>

</div>
{{/if}}

<div id="settings-default-perms" class="settings-default-perms" >
	<a href="#profile-jot-acl-wrapper" id="settings-default-perms-menu" class='popupbox'>{{$permissions}} {{$permdesc}}</a>
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
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>



<h3 class="settings-heading">{{$h_not}}</h3>
<div id="settings-notifications">

<div id="settings-activity-desc">{{$activity_options}}</div>

{{include file="field_checkbox.tpl" field=$post_newfriend}}
{{include file="field_checkbox.tpl" field=$post_joingroup}}
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
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>

