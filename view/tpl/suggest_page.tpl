<h3>$title</h3>

{{ if $entries }}
{{ for $entries as $child }}
{{ inc suggest_friends.tpl with $entry=$child }}{{ endinc }}
{{ endfor }}
{{ endif }}

<div class="clear"></div>
