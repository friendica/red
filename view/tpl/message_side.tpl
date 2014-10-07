<div class="widget">
	<h3>{{$title}}</h3>
	<ul class="nav nav-pills nav-stacked">
		<li><a href="{{$check.url}}"{{if $check.sel}} class="checkmessage-selected"{{/if}}>{{$check.label}}</a></li>
		<li><a href="{{$new.url}}"{{if $new.sel}} class="newmessage-selected"{{/if}}>{{$new.label}}</a></li>
	</ul>
	{{if $tabs}}
	<ul class="nav nav-pills nav-stacked">
		{{foreach $tabs as $t}}
			<li><a href="{{$t.url}}"{{if $t.sel}} class="message-selected"{{/if}}>{{$t.label}}</a></li>
		{{/foreach}}
	</ul>
	{{/if}}
</div>
