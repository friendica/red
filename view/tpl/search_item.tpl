<div id="thread-wrapper-{{$item.id}}" class="thread-wrapper {{$item.toplevel}}">
	<a name="{{$item.id}}" ></a>
	<div class="wall-item-outside-wrapper {{$item.indent}}{{$item.previewing}}{{if $item.owner_url}} wallwall{{/if}}" id="wall-item-outside-wrapper-{{$item.id}}" >
		<div class="wall-item-content-wrapper {{$item.indent}}" id="wall-item-content-wrapper-{{$item.id}}" style="clear:both;">
			<div class="wall-item-info{{if $item.owner_url}} wallwall{{/if}}" id="wall-item-info-{{$item.id}}" >
				<div class="wall-item-photo-wrapper{{if $item.owner_url}} wwfrom{{/if}}" id="wall-item-photo-wrapper-{{$item.id}}">
					<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-photo-link" id="wall-item-photo-link-{{$item.id}}"><img src="{{$item.thumb}}" class="wall-item-photo{{$item.sparkle}}" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" /></a>
				</div>
				<div class="wall-item-photo-end" style="clear:both"></div>
			</div>
			{{if $item.title}}
			<div class="wall-item-title" id="wall-item-title-{{$item.id}}"><h3>{{$item.title}}</h3></div>
			{{/if}}
			{{if $item.lock}}
			<div class="wall-item-lock dropdown">
				<i class="icon-lock lockview dropdown-toggle" data-toggle="dropdown" title="{{$item.lock}}" onclick="lockview(event,{{$item.id}});" ></i><ul id="panel-{{$item.id}}" class="lockview-panel dropdown-menu"></ul>&nbsp;
			</div>
			{{/if}}
			<div class="wall-item-author">
				<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.sparkle}}" id="wall-item-name-{{$item.id}}" >{{$item.name}}</span></a>{{if $item.owner_url}}&nbsp;{{$item.via}}&nbsp;<a href="{{$item.owner_url}}" title="{{$item.olinktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span></a>{{/if}}
			</div>
			<div class="wall-item-ago"  id="wall-item-ago-{{$item.id}}">
				{{if $item.verified}}<i class="icon-ok item-verified" title="{{$item.verified}}"></i>&nbsp;{{elseif $item.forged}}<i class="icon-remove item-forged" title="{{$item.forged}}"></i>&nbsp;{{/if}}{{if $item.location}}<span class="wall-item-location" id="wall-item-location-{{$item.id}}">{{$item.location}},&nbsp;</span>{{/if}}<span class="autotime" title="{{$item.isotime}}">{{$item.localtime}}{{if $item.editedtime}}&nbsp;{{$item.editedtime}}{{/if}}{{if $item.expiretime}}&nbsp;{{$item.expiretime}}{{/if}}</span>{{if $item.editedtime}}&nbsp;<i class="icon-pencil"></i>{{/if}}&nbsp;{{if $item.app}}<span class="item.app">{{$item.str_app}}</span>{{/if}}
			</div>
			<div class="wall-item-content" id="wall-item-content-{{$item.id}}">
				<div class="wall-item-title-end"></div>
				<div class="wall-item-body" id="wall-item-body-{{$item.id}}" >
					{{$item.body}}
				</div>
			</div>
				<div class="wall-item-tools">
				<div class="wall-item-tools-right btn-group pull-right">
					<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
						<i class="icon-caret-down"></i>
					</button>
					<ul class="dropdown-menu">
						{{if $item.item_photo_menu}}
						{{$item.item_photo_menu}}
						{{/if}}
						{{if $item.drop.dropping}}
						<li role="presentation" class="divider"></li>
						<li><a href="item/drop/{{$item.id}}" onclick="return confirmDelete();" title="{{$item.drop.delete}}" ><i class="icon-remove"></i> {{$item.drop.delete}}</a></li>
						{{/if}}
					</ul>
				</div>
			</div>
			{{* we dont' use this do we?
			{{if $item.drop.pagedrop}}
			<input type="checkbox" onclick="checkboxhighlight(this);" title="{{$item.drop.select}}" class="item-select" name="itemselected[]" value="{{$item.id}}" />
			{{/if}}
			*}}
			<div class="clear"></div>
		</div>
		<div class="wall-item-wrapper-end"></div>
		{{if $item.conv}}
		<div class="wall-item-conv" id="wall-item-conv-{{$item.id}}" >
			<a href='{{$item.conv.href}}' id='context-{{$item.id}}' title='{{$item.conv.title}}'>{{$item.conv.title}}</a>
		</div>
		{{/if}}
		<div class="wall-item-outside-wrapper-end {{$item.indent}}" ></div>
	</div>
</div>

