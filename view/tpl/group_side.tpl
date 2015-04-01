<div class="widget" id="group-sidebar">
	<h3>{{$title}}</h3>
	<div>
		<ul class="nav nav-pills nav-stacked">
			{{foreach $groups as $group}}
			<li>
				{{if $group.cid}}
				<a class="pull-right group-edit-tool fakelink" onclick="contactgroupChangeMember('{{$group.id}}','{{$group.enc_cid}}'); return true;"/>
					<i id="group-{{$group.id}}" class="{{if $group.ismember}}icon-check{{else}}icon-check-empty{{/if}}"></i>
				</a>
				{{/if}}
				{{if $group.edit}}
				<a class="pull-right group-edit-tool" href="{{$group.edit.href}}" title="{{$edittext}}"><i class="group-edit-icon icon-pencil"></i></a>
				{{/if}}
				<a{{if $group.selected}} class="group-selected"{{/if}} href="{{$group.href}}">{{$group.text}}</a>
			</li>
			{{/foreach}}
			<li>
				<a href="group/new" title="{{$createtext}}" >{{$createtext}}</a>
			</li>
		</ul>

	</div>
</div>




