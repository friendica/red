<div class="widget">
<h3>{{$header}}</h3>
{{$if $items}}
<table>
{{for $items as $item}}
<tr><td>{{$item.cr_name}}</td><td>{{$item.cr_inroom}}</td></tr>
{{/for}}
</table>
{{/if}}
</div>

