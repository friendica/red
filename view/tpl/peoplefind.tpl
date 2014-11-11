<div id="peoplefind-sidebar" class="widget">
	<h3>{{$findpeople}}</h3>
	<form action="directory" method="post" />
	<div class="form-group">
		<div class="input-group">
			<input class="widget-input" type="text" name="search" title="{{$hint}}" placeholder="{{$desc}}" />
		<div class="input-group-btn">
				<button class="btn btn-default btn-sm" type="submit" name="submit"><i class="icon-search"></i></button>
			</div>
		</div>
	</div>
	{{if $advanced_search}}
	<div class="form-group">
		<div id="advanced-people-search-div" class="input-group">
			<input class="widget-input" type="text" name="query" title="{{$advanced_hint}}" placeholder="{{$find_advanced}}" />
			<div class="input-group-btn">
				<button class="btn btn-default btn-sm" type="submit" name="submit"><i class="icon-search"></i></button>
			</div>
		</div>
	</div>
	{{/if}}
</form>
	<ul class="nav nav-pills nav-stacked">
		{{if $similar}}<li><a href="match" >{{$similar}}</a></li>{{/if}}
		{{if $loggedin}}<li><a href="suggest" >{{$suggest}}</a></li>{{/if}}
		<li><a href="randprof" >{{$random}}</a></li>
		{{if $loggedin}}{{if $inv}}<li><a href="invite" >{{$inv}}</a></li>{{/if}}{{/if}}
	</ul>
</div>
