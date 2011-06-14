<div id='adminpage'>
	<h1>$title - $page</h1>
	
	<p><span class='toggleplugin icon $status'></span> $info.name - $info.version : <a href="$baseurl/admin/plugins/$plugin/?a=t">$action</a></p>
	<p>$info.description</p>
	
	{{ for $info.author as $a }}
		<p class="author">{{ if $a.link }}<a href="$a.link"><span class='icon remote-link'></span></a>{{ endif }}$a.name</p>
	{{ endfor }}
	
	

	{{ if $readme }}
	<h3>Readme</h3>
	<div id="plugin_readme">
		$readme
	</div>
	{{ endif }}
</div>
