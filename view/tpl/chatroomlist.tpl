<div class="widget">
<h3>{{$header}}</h3>
{{if $items}}
<table>
{{foreach $items as $item}}
<tr><td align="left"><a href="{{$baseurl}}/chat/{{$nickname}}/{{$item.cr_id}}">{{$item.cr_name}}</a></td><td align="right">{{$item.cr_inroom}}</td></tr>
{{/foreach}}
</table>
{{/if}}
</div>

