<div class="widget">
<h3>{{$header}}</h3>
{{if $items}}
<table>
{{foreach $items as $item}}
<tr><td>{{$item.cr_name}}</td><td>{{$item.cr_inroom}}</td></tr>
{{/foreach}}
</table>
{{/if}}
</div>

