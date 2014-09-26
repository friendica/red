<div id="side-bar-photos-albums" class="widget">
	<h3>{{$title}}</h3>
	<ul class="nav nav-pills nav-stacked">
	{{if $upload}}
		<li><a href="{{$baseurl}}/photos/{{$nick}}/upload" title="{{$upload}}">{{$upload}}</a></li>
	{{/if}}
	{{if $albums}}
		{{foreach $albums as $al}}
		{{if $al.text}}
		<li><a href="{{$baseurl}}/photos/{{$nick}}/album/{{$al.bin2hex}}">{{$al.text}}<span class="badge pull-right">{{$al.total}}</span></a></li>
		{{/if}}
		{{/foreach}}

	{{/if}}
	</ul>
</div>
