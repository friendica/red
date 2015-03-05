<div class="generic-content-wrapper-styled">
<h1>{{$ptitle}}</h1>

<form action="settings/display" id="settings-form" method="post" autocomplete="off" >
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
{{if $theme}}
{{include file="field_themeselect.tpl" field=$theme}}
{{/if}}
{{if $mobile_theme}}
{{include file="field_themeselect.tpl" field=$mobile_theme}}
{{/if}}
{{if $expert}}
{{include file="field_checkbox.tpl" field=$user_scalable}}
{{/if}}
{{include file="field_input.tpl" field=$ajaxint}}
{{include file="field_input.tpl" field=$itemspage}}
{{include file="field_input.tpl" field=$channel_divmore_height}}
{{include file="field_input.tpl" field=$network_divmore_height}}
{{include file="field_checkbox.tpl" field=$nosmile}}
{{include file="field_checkbox.tpl" field=$title_tosource}}
{{include file="field_checkbox.tpl" field=$channel_list_mode}}
{{include file="field_checkbox.tpl" field=$network_list_mode}}

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>
{{if $expert}}
<br />
<a href="pdledit">{{$layout_editor}}</a>
<br />
{{/if}}
{{if $theme_config}}
<h2>Theme settings</h2>
{{$theme_config}}
{{/if}}

</form>
</div>
