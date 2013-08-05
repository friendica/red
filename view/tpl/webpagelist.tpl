{{include file="prettyphoto.tpl"}}

{{if $pages}}

	   <div id="pagelist-content-wrapper">
		{{foreach $pages as $key => $items}} 
				{{foreach $items as $item}}
					<div class="page-list-item"><a href="{{$baseurl}}/{{$item.url}}">{{$edit}}</a> | 
					<a href="page/{{$channel}}/{{$item.title}}">{{$view}}</a> 
					{{$item.title}} | 
					 <a href="page/{{$channel}}/{{$item.title}}?iframe=true&width=80%&height=80%" rel="prettyPhoto[iframes]">Preview</a> 

</div>
				{{/foreach}}
		{{/foreach}}
	   </div>
	
	   <div class="clear"></div>

{{/if}}
