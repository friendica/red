<ul class="tabs">
	{{foreach $tabs as $tab}}
		<li {{if $tab.id}}id="{{$tab.id}}"{{/if}}><a href="{{$tab.url}}" class="tab button {{$tab.sel}}"{{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a></li>
	{{/foreach}}
</ul>
<div class="tabs-end"></div>