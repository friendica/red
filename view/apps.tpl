<h3>$title</h3>

<ul>
	{{ for $apps as $ap }}
	<li><a href="$ap.url">$ap.name</a></li>
	{{ endfor }}
</ul>
