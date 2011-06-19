<div id='adminpage'>
	<h1>$title - $page</h1>
	
		<ul id='pluginslist'>
		{{ for $plugins as $p }}
			<li class='plugin $p.1'>
				<a class='toggleplugin' href='$baseurl/admin/plugins/$p.0?a=t' title="{{if $p.1==on }}Disable{{ else }}Enable{{ endif }}" ><span class='icon $p.1'></span></a>
				<a href='$baseurl/admin/plugins/$p.0'><span class='name'>$p.2.name</span></a> - <span class="version">$p.2.version</span>
					<div class='desc'>$p.2.description</div>
			</li>
		{{ endfor }}
		</ul>
</div>
