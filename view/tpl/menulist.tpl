<h1>{{$title}}</h1>

<a href="menu/new" title="{{$hintnew}}">{{$hintnew}}</a>

<br />

{{if $menus }}
<ul id="menulist">
{{foreach $menus as $m }}
<li><a href="menu/{{$m.menu_id}}" title="{{$hintedit}}"><i class="icon-pencil design-icons design-edit-icon"></i></a> <a href="menu/{{$m.menu_id}}/drop" title={{$hintdrop}}><i class="icon-remove drop-icons design-icons design-remove-icon"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="mitem/{{$m.menu_id}}/new" title="{{$hintcontent}}">{{$m.menu_name}}</a></li>
{{/foreach}}
</ul>
{{/if}}

 

