{{if $pages}}
<div id="pagelist-content-wrapper">
{{foreach $pages as $key => $items}} 
<ul class="page-list">
{{foreach $items as $item}}
<li><a href="editwebpage/{{$item.url}}">{{$editlink}}</a> {{$item.title}}</li>
{{/foreach}}
</ul>
<div class="clear"></div>
</div>
{{/foreach}}
{{/if}}
