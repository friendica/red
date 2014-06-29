<h1>{{$header}}</h1>
<table id="cloud-index">
	<tr>
		<th></th>
		<th>{{t('Name')}}&nbsp;&nbsp;&nbsp;</th>
		<th></th><th></th><th></th>
		<th>{{t('Type')}}&nbsp;&nbsp;&nbsp;</th>
		<th>{{t('Size')}}&nbsp;&nbsp;&nbsp;</th>
		<th>{{t('Last modified')}}</th>
	</tr>
	<tr><td colspan="8"><hr></td></tr>
{{if $parentpath}}
	<tr>
		<td>{{$parentpath.icon}}</td>
		<td><a href="{{$parentpath.path}}" title="{{t('parent')}}">..</a></td>
		<td></td><td></td><th></td>
		<td>[{{t('parent')}}]</td>
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
		<td style="position:relative;"><a href="{{$item.fileStorageUrl}}/{{$item.attachId}}/edit" title="{{t('Edit')}}"><i class="icon-pencil btn btn-default"></i></a></td>
		<td><a href="{{$item.fileStorageUrl}}/{{$item.attachId}}/delete" title="{{t('Delete')}}" onclick="return confirm('{{t('Are you sure you want to delete this item?')}}');"><i class="icon-remove btn btn-default drop-icons"></i></a></td>
{{else}}
		<td></td><td></td><td></td>
{{/if}}
		<td>{{$item.type}}</td>
		<td>{{$item.sizeFormatted}}</td>
		<td>{{$item.lastmodified}}</td>
	</tr>
{{/foreach}}
	<tr><td colspan="8"><hr></td></tr>
</table>

{{if $quota.limit || $quota.used}}
	<p><strong>{{t('Total')}}</strong> {{$quota.desc}}</p>
{{/if}}