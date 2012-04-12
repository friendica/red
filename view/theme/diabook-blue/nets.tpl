<div id="nets-sidebar" class="widget">
	<h3>$title</h3>
	<div id="nets-desc">$desc</div>

	<ul class="nets-ul">
	<li><a style="text-decoration: none;" class="tool" href="$base" class="nets-link{{ if $sel_all }} nets-selected{{ endif }} nets-all">$all</a></li>
	{{ for $nets as $net }}
	<li class="tool"><a href="$base?nets=$net.ref" class="nets-link{{ if $net.selected }} nets-selected{{ endif }}">$net.name</a></li>
	{{ endfor }}
	</ul>
</div>
