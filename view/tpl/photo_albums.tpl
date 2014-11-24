<div id="side-bar-photos-albums" class="widget">
	<h3>{{$title}}</h3>
	<ul class="nav nav-pills nav-stacked">
		<li><a href="{{$baseurl}}/photos/{{$nick}}" title="{{$title}}" >Recent Photos</a></li>
		{{if $albums}}
		{{foreach $albums as $al}}
		{{if $al.text}}
		<li><a href="{{$baseurl}}/photos/{{$nick}}/album/{{$al.bin2hex}}"><span class="badge pull-right">{{$al.total}}</span>{{$al.text}}</a></li>
		{{/if}}
		{{/foreach}}
		{{/if}}
	</ul>
</div>
