<h1>{{$title}}</h1>
<h2>{{$pass}}</h2>
<form  action="{{$baseurl}}/index.php?q=setup" method="post">
<table>
{{foreach $checks as $check}}
	<tr><td>{{$check.title}} </td><td><i class="{{if $check.status}}icon-check{{else}}{{if $check.required}}icon-check-empty{{else}}icon-exclamation{{/if}}{{/if}}"></i></td><td>{{if $check.required}}(required){{/if}}</td></tr>
	{{if $check.help}}
	<tr><td colspan="3"><blockquote>{{$check.help}}</blockquote></td></tr>
	{{/if}}
{{/foreach}}
</table>

{{if $phpath}}
	<input type="hidden" name="phpath" value="{{$phpath}}">
{{/if}}

{{if $passed}}
	<input type="hidden" name="pass" value="2">
	<input type="submit" value="{{$next}}">
{{else}}
	<input type="hidden" name="pass" value="1">
	<input type="submit" value="{{$reload}}">
{{/if}}
</form>
