<h4><a href="$admurl">Admin</a></h4>
<ul>
	<li class='admin link $admin.site.2'><a href='$admin.site.0'>$admin.site.1</a></li>
	<li class='admin link $admin.users.2'><a href='$admin.users.0'>$admin.users.1</a></li>
	<li class='admin link $admin.plugins.2'><a href='$admin.plugins.0'>$admin.plugins.1</a></li>
</ul>


{{ if $admin.plugins_admin }}<h4>Plugins</h4>{{ endif }}
<ul>
	{{ for $admin.plugins_admin as $l }}
	<li class='admin link $l.2'><a href='$l.0'>$l.1</a></li>
	{{ endfor }}
</ul>
	
	
<h4>Logs</h4>
<ul>
	<li class='admin link $admin.logs.2'><a href='$admin.logs.0'>$admin.logs.1</a></li>
</ul>
