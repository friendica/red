	<div class='form-group field input'>
		<label for='id_{{$field.0}}' id='label_{{$field.0}}'>{{$field.1}}</label>
		<input class="form-control" name='{{$field.0}}' id='id_{{$field.0}}' type="text" value="{{$field.2}}">{{if $field.4}} <span class="required">{{$field.4}}</span> {{/if}}
		<span id='help_{{$field.0}}' class='help-block'>{{$field.3}}</span>
		<div class="clear"></div>
	</div>
