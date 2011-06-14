<div id='adminpage'>
	<h1>$title - $page</h1>
	
	<p><span class='toggleplugin icon $status'></span> $info.name - $info.version : <a href="$baseurl/admin/plugins/$plugin/?a=t">$action</a></p>
	<p>$info.description</p>
	<p>
	{{ for $info.author as $a }}
	<a href="$a.link">$a.name</a> 
	{{ endfor }}
	</p>
	

	{{ if $readme }}
	<h3>Readme</h3>
	<div id="plugin_readme">
		$readme
	</div>
	{{ endif }}
</div>
