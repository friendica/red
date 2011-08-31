<h4 class="contact-h4">$contacts</h4>
{{ if $micropro }}
	<div id="contact-block">
		{{ for $micropro as $m }}
			$m
		{{ endfor }}
	</div>
	<div id="viewcontacts"><a id="viewcontacts-link" href="viewcontacts/$nickname">$viewcontacts</a></div>
{{ endif }}
