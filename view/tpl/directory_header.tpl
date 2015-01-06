<div class="generic-content-wrapper-styled">
<h1>{{$dirlbl}}</h1>

{{if $search}}
<h4>{{$finddsc}} {{$safetxt}}</h4> 
{{/if}}

{{foreach $entries as $entry}}
{{include file="direntry.tpl"}}
{{/foreach}}

<div id="page-end"></div>
<div class="directory-end"></div>
</div>
<script>$(document).ready(function() { loadingPage = false;});</script>
<div id="page-spinner"></div>
