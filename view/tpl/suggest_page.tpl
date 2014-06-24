<h3>{{$title}}</h3>

{{if $entries}}
{{foreach $entries as $child}}
{{include file="suggest_friends.tpl" entry=$child}}
{{/foreach}}
{{/if}}

<div class="clear"></div>
