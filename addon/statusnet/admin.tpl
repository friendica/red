{{ for $sites as $s }}
	{{ inc field_input.tpl with $field=$s.sitename }}{{ endinc }}
	{{ inc field_input.tpl with $field=$s.apiurl }}{{ endinc }}
	{{ inc field_input.tpl with $field=$s.secret }}{{ endinc }}
	{{ inc field_input.tpl with $field=$s.key }}{{ endinc }}
	{{ if $s.delete }}
		{{ inc field_checkbox.tpl with $field=$s.delete }}{{ endinc }}
		<hr>
	{{ else }}
		<p>Fill this form to add a new site</p>
	{{ endif }}
	
{{ endfor }}


<div class="submit"><input type="submit" name="page_site" value="$submit" /></div>
