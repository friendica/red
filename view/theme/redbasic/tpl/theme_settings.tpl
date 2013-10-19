{{include file="field_select.tpl" field=$schema}}
<div class="settings-submit-wrapper">
	<input type="submit" value="{{$submit}}" class="settings-submit" name="redbasic-settings-submit" />
</div>

{{if $expert}}
{{include file="field_select.tpl" field=$nav_colour}}
{{include file="field_input.tpl" field=$bgcolour}}
{{include file="field_input.tpl" field=$background_image}}
{{include file="field_input.tpl" field=$item_colour}}
{{include file="field_input.tpl" field=$item_opacity}}
{{include file="field_input.tpl" field=$font_size}}
{{include file="field_input.tpl" field=$font_colour}}
{{include file="field_input.tpl" field=$radius}}
{{include file="field_input.tpl" field=$shadow}}
{{include file="field_input.tpl" field=$section_width}}
<div class="settings-submit-wrapper">
	<input type="submit" value="{{$submit}}" class="settings-submit" name="redbasic-settings-submit" />
</div>
{{/if}}