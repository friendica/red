<div id="lang-select-icon" class="icon s22 language" title="{{$title}}" onclick="openClose('language-selector');" ></div>
<div id="language-selector" style="display: none;" >
	<form action="#" method="post" >
		<select name="system_language" onchange="this.form.submit();" >
			{{foreach $langs.0 as $v=>$l}}
				<option value="{{$v}}" {{if $v==$langs.1}}selected="selected"{{/if}}>{{$l}}</option>
			{{/foreach}}
		</select>
	</form>
</div>
