<ul class="tabs">
	{{ for $tabs as $tab }}
		<li {{ if $tab.id }}id="$tab.id"{{ endif }}><a href="$tab.url" class="tab button $tab.sel"{{ if $tab.title }} title="$tab.title"{{ endif }}>$tab.label</a></li>
	{{ endfor }}
</ul>
<div class="tabs-end"></div>