<div id='adminpage'>
	<h1>$title - $page</h1>

	<dl>
		<dt>$pending.0</dt>
		<dd>$pending.1</dt>
	</dl>

	<dl>
		<dt>$users.0</dt>
		<dd>$users.1</dd>
	</dl>
	{{ for $accounts as $p }}
		<dl>
			<dt>$p.0</dt>
			<dd>$p.1</dd>
		</dl>
	{{ endfor }}


	<dl>
		<dt>$plugins.0</dt>
		
		{{ for $plugins.1 as $p }}
			<dd>$p</dd>
		{{ endfor }}
		
	</dl>

	<dl>
		<dt>$version.0</dt>
		<dd>$version.1 - $build</dt>
	</dl>


</div>
