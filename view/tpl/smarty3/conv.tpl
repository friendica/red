{{foreach $threads as $thread}}
<div id="thread-wrapper-{{$thread.id}}" class="thread-wrapper">
	{{foreach $thread.items as $item}}
		{{if $item.comment_firstcollapsed}}
			<div class="hide-comments-outer">
			<span id="hide-comments-total-{{$thread.id}}" class="hide-comments-total">{{$thread.num_comments}}</span> <span id="hide-comments-{{$thread.id}}" class="hide-comments fakelink" onclick="showHideComments({{$thread.id}});">{{$thread.hide_text}}</span>
			</div>
			<div id="collapsed-comments-{{$thread.id}}" class="collapsed-comments" style="display: none;">
		{{/if}}
		{{if $item.comment_lastcollapsed}}</div>{{/if}}
		
		{{include file="{{$item.template}}"}}
		
		
	{{/foreach}}
</div>
{{/foreach}}
