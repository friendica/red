<div class="field custom">
<label for="group-selection" id="group-selection-lbl">$label</label>
<select name="group-selection" id="group-selection" >
{{ for $groups as $group }}
<option value="$group.id" {{ if $group.selected }}selected="selected"{{ endif }} >$group.name</option>
{{ endfor }}
</select>
</div>
