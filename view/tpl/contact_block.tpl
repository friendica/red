<div id="contact-block">
<div id="contact-block-numcontacts">{{$contacts}}</div>
{{if $micropro}}
		<a class="allcontact-link" href="viewconnections/{{$nickname}}">{{$viewconnections}}</a>
		<div class='contact-block-content'>
		{{foreach $micropro as $m}}
			{{$m}}
		{{/foreach}}
		</div>
{{/if}}
</div>
<div class="clear"></div>
