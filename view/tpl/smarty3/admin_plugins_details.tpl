<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>
	
	<p><span class='toggleplugin icon {{$status}}'></span> {{$info.name}} - {{$info.version}} : <a href="{{$baseurl}}/admin/{{$function}}/{{$plugin}}/?a=t&amp;t={{$form_security_token}}">{{$action}}</a></p>
	<p>{{$info.description}}</p>
	
	<p class="author">{{$str_author}}
	{{foreach $info.author as $a}}
		{{if $a.link}}<a href="{{$a.link}}">{{$a.name}}</a>{{else}}{{$a.name}}{{/if}},
	{{/foreach}}
	</p>

	<p class="maintainer">{{$str_maintainer}}
	{{foreach $info.maintainer as $a}}
		{{if $a.link}}<a href="{{$a.link}}">{{$a.name}}</a>{{else}}{{$a.name}}{{/if}},
	{{/foreach}}
	</p>
	
	{{if $screenshot}}
	<a href="{{$screenshot.0}}" class='screenshot'><img src="{{$screenshot.0}}" alt="{{$screenshot.1}}" /></a>
	{{/if}}

	{{if $admin_form}}
	<h3>{{$settings}}</h3>
	<form method="post" action="{{$baseurl}}/admin/{{$function}}/{{$plugin}}/">
		{{$admin_form}}
	</form>
	{{/if}}

	{{if $readme}}
	<h3>Readme</h3>
	<div id="plugin_readme">
		{{$readme}}
	</div>
	{{/if}}
</div>
