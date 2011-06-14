<div id='adminpage'>
	<h1>$title - $page</h1>
	
		<ul id='pluginslist'>
		{{ for $plugins as $p }}
			<li class='plugin $p.1'>
				<a class='toggle' href='$baseurl/admin/plugins/$p.0?a=t'><span class='icon $p.1'></span></a>
				<a href='$baseurl/admin/plugins/$p.0'>
					<span class='name'>$p.0</span>
				</a>
			</li>
		{{ endfor }}
		</ul>
</div>
