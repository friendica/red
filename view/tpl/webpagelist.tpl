{{include file="prettyphoto.tpl"}}

{{if $pages}}

	   <div id="pagelist-content-wrapper" class="generic-content-wrapper">
		{{foreach $pages as $key => $items}} 
				{{foreach $items as $item}}
					<div class="page-list-item">
					{{if $edit}}<a href="{{$baseurl}}/{{$item.url}}" title="{{$edit}}"><i class="icon-pencil design-icons design-edit-icon"></i></a> {{/if}}
					{{if $view}}<a href="page/{{$channel}}/{{$item.title}}" title="{{$view}}"><i class="icon-external-link design-icons design-view-icon"></i></a> {{/if}}
					{{if $preview}}<a href="page/{{$channel}}/{{$item.title}}?iframe=true&width=80%&height=80%" rel="xprettyPhoto[iframesx]" title="{{$preview}}"><i class="icon-eye-open design-icons design-preview-icon"></i></a> {{/if}}
					{{$item.title}}
					</div>
				{{/foreach}}
		{{/foreach}}
	   </div>
	
	   <div class="clear"></div>

{{/if}}
