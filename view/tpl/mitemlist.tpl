<h1>{{$title}}</h1>
<h2>{{$menudesc}} ({{$menuname}})</h2>

<a href="menu/{{$menu_id}}" title="{{$hintmenu}}">{{$edmenu}}</a><br />
<a href="mitem/{{$menu_id}}/new" title="{{$hintnew}}">{{$hintnew}}</a>

<br />
<br />

{{if $mlist }}
<ul id="mitemlist" class="menu-item-list">
{{foreach $mlist as $m }}
<li><a href="mitem/{{$menu_id}}/{{$m.mitem_id}}" title="{{$hintedit}}"><i class="icon-pencil mitem-edit"></i></a><a href="mitem/{{$menu_id}}/{{$m.mitem_id}}/drop" title={{$hintdrop}}><i class="icon-remove"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="mitem/{{$menu_id}}/{{$m.mitem_id}}" title="{{$hintcontent}}">{{$m.mitem_desc}}</a> ({{$m.mitem_link}})</li>
{{/foreach}}
</ul>
{{/if}}

 

