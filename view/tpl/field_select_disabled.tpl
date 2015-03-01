	<div class='form-group field select'>
		<label style="font-weight: normal;" for='id_{{$field.0}}'>{{$field.1}}</label>
		<select class="form-control" disabled="true" name='{{$field.0}}' id='id_{{$field.0}}'>
			{{foreach $field.4 as $opt=>$val}}<option value="{{$opt}}" {{if $opt==$field.2}}selected="selected"{{/if}}>{{$val}}</option>{{/foreach}}
		</select>
		<span class='help-block'>{{$field.3}}</span>
	</div>
