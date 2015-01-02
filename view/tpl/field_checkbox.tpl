	<div class='field checkbox'>
		<label class="mainlabel" for='id_{{$field.0}}'>{{$field.1}}</label>
		<div><input type="checkbox" name='{{$field.0}}' id='id_{{$field.0}}' value="1" {{if $field.2}}checked="checked"{{/if}}><label class="switchlabel" for='id_{{$field.0}}'> <span class="onoffswitch-inner" data-on='{{if $field.4}}{{$field.4.1}}{{/if}}' data-off='{{if $field.4}}{{$field.4.0}}{{/if}}'></span><span class="onoffswitch-switch"></span> </label></div><span class='field_help'>{{$field.3}}</span>
	</div>
