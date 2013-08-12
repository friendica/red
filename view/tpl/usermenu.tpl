<div class="pmenu">
  <div class="pmenu-title">{{$menu.menu_desc}}</div>
{{if $items }}
<ul class="pmenu-body">
{{foreach $items as $mitem }}
<li class="pmenu-item"><a href="{{$mitem.mitem_link}}">{{$mitem.mitem_desc}}</a></li>
{{/foreach }}
</ul>
{{/if}}
<div class="pmenu-end"></div>
</div>
