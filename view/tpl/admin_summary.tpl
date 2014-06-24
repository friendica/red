<div class="generic-content-wrapper" id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>

	<dl>
		<dt>{{$queues.label}}</dt>
		<dd>{{$queues.queue}}</dd>
	</dl>
	<dl>
		<dt>{{$pending.0}}</dt>
		<dd>{{$pending.1}}</dt>
	</dl>

	<dl>
		<dt>{{$users.0}}</dt>
		<dd>{{$users.1}}</dd>
	</dl>
	<!-- $accounts is empty
		{{foreach $accounts as $p}}
		<dl>
			<dt>{{$p.0}}</dt>
			<dd>{{if $p.1}}{{$p.1}}{{else}}0{{/if}}</dd>
		</dl>
	{{/foreach}} -->
	<dl>
		<dt>{{$plugins.0}}</dt>
		<dd>
		{{foreach $plugins.1 as $p}} {{$p}} {{/foreach}}
		&nbsp;
		</dd>
	</dl>
	<dl>
		<dt>{{$version.0}}</dt>
		<dd>{{$version.1}} - {{$build}}</dd>
	</dl>

</div>
