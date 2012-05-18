<select name="group-selection" id="group-selection">
{{ for $groups as $group }}
<option value="$group.id" {{ if $group.selected }}selected="selected"{{ endif }} >$group.name</option>
{{ endfor }}
</select>
