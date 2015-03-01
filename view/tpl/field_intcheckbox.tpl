	<div class="form-group field checkbox">
		<label for='id_{{$field.0}}'>{{$field.1}}</label>
		<div class="pull-right"><input type="checkbox" name='{{$field.0}}' id='id_{{$field.0}}' value="{{$field.3}}" {{if $field.2}}checked="checked"{{/if}}><label class="switchlabel" for='id_{{$field.0}}'> <span class="onoffswitch-inner" data-on='{{if $field.5}}{{$field.5.1}}{{/if}}' data-off='{{if $field.5}}{{$field.5.0}}{{/if}}'></span><span class="onoffswitch-switch"></span> </label></div>
		<span class='help-block'>{{$field.4}}</span>
	</div>
	<div class="clear"></div>
