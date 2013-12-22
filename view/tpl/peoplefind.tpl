<div id="peoplefind-sidebar" class="widget">
	<h3>{{$findpeople}}</h3>
	{{$desc}}
	<form action="directory" method="post" />
		<input class="icon-search" id="side-peoplefind-url" type="text" name="search" size="24" title="{{$hint}}" placeholder="&#xf002;"/>
		<input id="side-peoplefind-submit" type="submit" name="submit" value="{{$findthem}}" />
	</form>
	<br />
	{{if $similar}}<a href="match" >{{$similar}}</a><br />{{/if}}
	{{if $loggedin}}<a href="suggest" >{{$suggest}}</a><br />{{/if}}
	<a href="randprof" >{{$random}}</a><br />
	{{if $loggedin}}{{if $inv}}<a href="invite" >{{$inv}}</a>{{/if}}{{/if}}
</div>

