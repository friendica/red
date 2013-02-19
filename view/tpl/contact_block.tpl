<div id="contact-block">
<div id="contact-block-numcontacts">$contacts</div>
{{ if $micropro }}
		<a class="allcontact-link" href="viewconnections/$nickname">$viewconnections</a>
		<div class='contact-block-content'>
		{{ for $micropro as $m }}
			$m
		{{ endfor }}
		</div>
{{ endif }}
</div>
<div class="clear"></div>
