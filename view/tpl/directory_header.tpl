<h1>$dirlbl</h1>

{{ if $search }}
<h4>$finddsc $safetxt</h4> 
{{ endif }}

{{for $entries as $entry}}

{{ inc direntry.tpl }}{{ endinc }}

{{ endfor }}



<div class="directory-end"></div>

