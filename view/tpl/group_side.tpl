<div class="widget" id="group-sidebar">
	<h3>{{$title}}</h3>
	<div>
		<ul class="nav nav-pills nav-stacked">
			{{foreach $groups as $group}}
			<li>
				{{if $group.cid}}
				<input type="checkbox" 
					class="{{if $group.selected}}ticked{{else}}unticked {{/if}} action" 
					onclick="contactgroupChangeMember('{{$group.id}}','{{$group.enc_cid}}');return true;"
					{{if $group.ismember}}checked="checked"{{/if}}
				/>
				{{/if}}
				{{if $group.edit}}
				<a class="pull-right group-edit-icon" href="{{$group.edit.href}}" title="{{$edittext}}"><i class="icon-pencil"></i></a>
				{{/if}}
				<a class="{{if $group.selected}}group-selected{{/if}}" href="{{$group.href}}">{{$group.text}}</a>
			</li>
			{{/foreach}}
			<li>
				<a href="group/new">{{$createtext}}</a>
			</li>
		</ul>
	</div>
</div>




