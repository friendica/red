	
	<div class='field acheckbox'>
		<label for='id_$field.0'>$field.1</label>
		<input type="checkbox" class="abook-edit-them" name='$field.0' id='id_$field.0' value="1" disabled="disabled" {{ if $field.2 }}checked="checked"{{ endif }} />
		<input type="checkbox" class="abook-edit-me" name='$field.0' id='id_$field.0' value="1" {{ if $field.2 }}checked="checked"{{ endif }} />
		<span class='field_abook_help'>$field.3</span>
	</div>
