<h2>$banner</h2>

<div id="failed_updates_desc">$desc</div>

{{ if $failed }}
{{ for $failed as $f }}

<h4>$f</h4>
<ul>
<li><a href="$base/admin/dbsync/mark/$f">$mark</a></li>
<li><a href="$base/admin/dbsync/$f">$apply</a></li>
</ul>

<hr />
{{ endfor }}
{{ endif }}

