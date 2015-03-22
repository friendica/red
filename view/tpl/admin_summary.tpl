<div class="generic-content-wrapper-styled" id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>
{{if $adminalertmsg}}
	<p class="alert alert-warning" role="alert">{{$adminalertmsg}}</p>
{{/if}}
	<dl>
		<dt>{{$queues.label}}</dt>
		<dd>{{$queues.queue}}</dd>
	</dl>
	<dl>
		<dt>{{$accounts.0}}</dt>
		<dd>{{foreach from=$accounts.1 item=acc name=account}}<span title="{{$acc.label}}">{{$acc.val}}</span>{{if !$smarty.foreach.account.last}} / {{/if}}{{/foreach}}</dd>
	</dl>
	<dl>
		<dt>{{$pending.0}}</dt>
		<dd>{{$pending.1}}</dt>
	</dl>
	<dl>
		<dt>{{$channels.0}}</dt>
		<dd>{{foreach from=$channels.1 item=ch name=chan}}<span title="{{$ch.label}}">{{$ch.val}}</span>{{if !$smarty.foreach.chan.last}} / {{/if}}{{/foreach}}</dd>
	</dl>
	<dl>
		<dt>{{$plugins.0}}</dt>
		<dd>
		{{foreach $plugins.1 as $p}} {{$p}} {{/foreach}}
		</dd>
	</dl>
	<dl>
		<dt>{{$version.0}}</dt>
		<dd>{{$version.1}} - {{$build}}</dd>
	</dl>
</div>