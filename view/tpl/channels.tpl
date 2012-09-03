<h3>$header</h3>


{{ if $links }}
{{ for $links as $l }}
<a class="channels-links" href="$l.0" title="$l.1">$l.2</a>
{{ endfor }}
{{ endif }} 

<div id="channels-desc" class="descriptive-text">$desc</div>

{{ for $all_channels as $chn }}
{{ inc channel.tpl with $channel=$chn }}{{ endinc }}
{{ endfor }} 

<div class="channels-end"></div>
