<div id="lang-select-icon" title="{{$title}}" onclick="openClose('language-selector');" ><i class="icon-flag"></i></div>
<div id="language-selector" style="display: none;" >
	<form action="#" method="post" >
		<select name="system_language" onchange="this.form.submit();" >
			{{foreach $langs.0 as $v=>$l}}
				<option value="{{$v}}" {{if $v==$langs.1}}selected="selected"{{/if}}>{{$l}}</option>
			{{/foreach}}
		</select>
	</form>
</div>
