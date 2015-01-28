<div class="section-title-wrapper">
	{{if $actionspanel}}
	<div class="pull-right">
		{{if $is_owner}}
		<a href="/sharedwithme" class="btn btn-xs btn-default"><i class="icon-cloud-download"></i>&nbsp;{{$shared}}</a>
		{{/if}}
		<button id="files-create-btn" class="btn btn-xs btn-primary" title="{{if $quota.limit || $quota.used}}{{$quota.desc}}{{/if}}" onclick="openClose('files-mkdir-tools'); closeMenu('files-upload-tools');"><i class="icon-folder-close-alt"></i>&nbsp;{{$create}}</button>
		<button id="files-upload-btn" class="btn btn-xs btn-success" title="{{if $quota.limit || $quota.used}}{{$quota.desc}}{{/if}}" onclick="openClose('files-upload-tools'); closeMenu('files-mkdir-tools');"><i class="icon-upload"></i>&nbsp;{{$upload}}</button>
	</div>
	{{/if}}
	<h2>{{$header}}</h2>
	<div class="clear"></div>
</div>
{{if $actionspanel}}
	{{$actionspanel}}
{{/if}}
