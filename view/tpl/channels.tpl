<h3>$header</h3>

<div id="channels-desc" class="descriptive-text">$desc</div>

{{ if $links }}
{{ for $links as $l }}
<a class="channels-links" href="$l.0" title="$l.1">$l.2</a>
{{ endfor }}
{{ endif }} 

<div align="center">{{ inc channel.tpl with $chn = $active }}</div>
<div align="center">$act_desc</div>


{{ for $all_channels as $chn }}
{{ inc channel.tpl with $channel = $chn }}
{{ endfor }} 

<div class="channels-end"></div>
