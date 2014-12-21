<div class="generic-content-wrapper-styled">

<table id="cloud-index">
	<tr>
		<th></th>
		<th>{{$name}}&nbsp;&nbsp;&nbsp;</th>
		<th></th><th></th><th></th>
		<th>{{$type}}&nbsp;&nbsp;&nbsp;</th>
		<th>{{$size}}&nbsp;&nbsp;&nbsp;</th>
		<th>{{$lastmod}}</th>
	</tr>
	<tr><td colspan="8"><hr></td></tr>
{{if $parentpath}}
	<tr>
		<td>{{$parentpath.icon}}</td>
		<td><a href="{{$parentpath.path}}" title="{{$parent}}">..</a></td>
		<td></td><td></td><th></td>
		<td>[{{$parent}}]</td>
		<td></td>
		<td></td>
	</tr>
{{/if}}
{{foreach $entries as $item}}
	<tr>
		<td>{{$item.icon}}</td>
		<td style="min-width: 15em"><a href="{{$item.fullPath}}">{{$item.displayName}}</a></td>
{{if $item.is_owner}}
		<td>{{$item.attachIcon}}</td>
		<td style="position:relative;"><i id="file-edit-{{$item.attachId}}" class="fakelink icon-pencil" onclick="filestorage(event, '{{$nick}}', {{$item.attachId}});"></i></td>
		<td><a href="{{$item.fileStorageUrl}}/{{$item.attachId}}/delete" title="{{$delete}}" onclick="return confirmDelete();"><i class="icon-remove drop-icons"></i></a></td>

{{else}}
		<td></td><td></td><td></td>
{{/if}}
		<td>{{$item.type}}</td>
		<td>{{$item.sizeFormatted}}</td>
		<td>{{$item.lastmodified}}</td>
	</tr>
	<tr><td id="perms-panel-{{$item.attachId}}" colspan="8"></td></tr>
{{/foreach}}

</table>
</div>
