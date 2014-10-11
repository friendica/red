{{$listtitle}}
{{if $pages}}

	   <div id="pagelist-content-wrapper" class="generic-content-wrapper-styled">
		<table class="webpage-list-table">
		<tr><td>{{$actions_txt}}</td><td>{{$pagelink_txt}}</td><td>{{$title_txt}}</td><td>{{$created_txt}}</td><td>{{$edited_txt}}</td></tr>
		{{foreach $pages as $key => $items}} 
				{{foreach $items as $item}}
					<tr>
					<td>
					{{if $edit}}<a href="{{$baseurl}}/{{$item.url}}" title="{{$edit}}"><i class="icon-pencil design-icons design-edit-icon btn btn-default"></i></a> {{/if}}
					{{if $view}}<a href="page/{{$channel}}/{{$item.pagetitle}}" title="{{$view}}"><i class="icon-external-link design-icons design-view-icon btn btn-default"></i></a> {{/if}}
					{{if $preview}}<a href="page/{{$channel}}/{{$item.pagetitle}}?iframe=true&width=80%&height=80%" title="{{$preview}}" class="webpage-preview" ><i class="icon-eye-open design-icons design-preview-icon btn btn-default"></i></a> {{/if}}
					</td>
					<td>
					{{if $view}}<a href="page/{{$channel}}/{{$item.pagetitle}}" title="{{$view}}">{{$item.pagetitle}}</a>
					{{else}}{{$item.pagetitle}}
					{{/if}}
					</td>
					<td>
					{{$item.title}}
					</td>
					<td>
					{{$item.created}}
					</td>
					<td>
					{{$item.edited}}
					</td>
					</tr>
				{{/foreach}}
		{{/foreach}}

		</table>
	   </div>
	
	   <div class="clear"></div>

{{/if}}
