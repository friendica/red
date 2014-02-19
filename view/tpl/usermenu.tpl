<div class="pmenu">
{{if $menu.menu_desc}}
  <h3 class="pmenu-title">{{$menu.menu_desc}}</h3>
{{/if}}
{{if $edit}}
<a href="mitem/{{$menu.menu_id}}" title="{{$edit}}"><i class="icon-pencil fakelink" title="{{$edit}}"></i></a>
{{/if}}
{{if $items }}
<ul class="pmenu-body">
{{foreach $items as $mitem }}
<li class="pmenu-item"><a href="{{$mitem.mitem_link}}" {{if $mitem.newwin}}target="_blank"{{/if}}>{{$mitem.mitem_desc}}</a></li>
{{/foreach }}
</ul>
{{/if}}
<div class="pmenu-end"></div>
</div>
