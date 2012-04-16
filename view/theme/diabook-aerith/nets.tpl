<div id="nets-sidebar" class="widget">
	<h3>$title</h3>
	<div id="nets-desc">$desc</div>

	<ul class="nets-ul">
	<li class="tool {{ if $sel_all }}selected{{ endif }}"><a style="text-decoration: none;" href="$base" class="nets-link nets-all">$all</a></li>
	{{ for $nets as $net }}
	<li class="tool {{ if $net.selected }}selected{{ endif }}"><a href="$base?nets=$net.ref" class="nets-link nets-selected">$net.name</a></li>
	{{ endfor }}
	</ul>
</div>
