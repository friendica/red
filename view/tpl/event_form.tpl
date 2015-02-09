<div class="generic-content-wrapper-styled">

<h3>{{$title}}</h3>

<p>
{{$desc}}
</p>

<form action="{{$post}}" method="post" >

<input type="hidden" name="event_id" value="{{$eid}}" />
<input type="hidden" name="event_hash" value="{{$event_hash}}" />
<input type="hidden" name="xchan" value="{{$xchan}}" />
<input type="hidden" name="mid" value="{{$mid}}" />

<div id="event-summary-text">{{$t_text}}</div>
<input type="text" id="event-summary" name="summary" value="{{$t_orig}}" />{{$required}}

<div class="clear"></div>

<div id="event-start-text">{{$s_text}}</div>

{{$s_dsel}}

<div class="clear"></div><br />

   <div class='field checkbox'>
   <label class="mainlabel" for='id_nofinish'>{{$n_text}}</label>
   <div><input type="checkbox" name='nofinish' id='id_nofinish' value="1" {{$n_checked}} onclick="showHideFinishDate(); return true;" >
	<label class="switchlabel" for='id_nofinish'> <span class="onoffswitch-inner" data-on='' data-off='' ></span>
	<span class="onoffswitch-switch"></span> </label></div><span class='field_help'></span>
    </div>

<div id="event-nofinish-break"></div>

<div id="event-finish-wrapper">
<div id="event-finish-text">{{$f_text}}</div>
{{$f_dsel}}
</div>

<div id="event-datetime-break"></div>

{{include file="field_checkbox.tpl" field=$adjust}}

<div id="event-adjust-break"></div>



{{if $catsenabled}}
<div id="event-category-wrap">
	<input name="category" id="event-category" type="text" placeholder="{{$placeholdercategory}}" value="{{$category}}" class="event-cats" style="display: block;" data-role="tagsinput"  />
</div>
{{/if}}



<div id="event-desc-text">{{$d_text}}</div>

<textarea id="comment-edit-text-desc" class="comment-edit-text-full" name="desc" >{{$d_orig}}</textarea>
<div class="clear"></div>
<div id="comment-tools-desc" class="comment-tools" style="display: block;" >
	<div id="comment-edit-bb-desc" class="btn-toolbar pull-left">
		<div class='btn-group'>
			<button class="btn btn-default btn-xs" title="{{$edbold}}" onclick="insertbbcomment('{{$comment}}','b', 'desc'); return false;">
				<i class="icon-bold comment-icon"></i>
			</button>
			<button class="btn btn-default btn-xs" title="{{$editalic}}" onclick="insertbbcomment('{{$comment}}','i', 'desc'); return false;">
				<i class="icon-italic comment-icon"></i>
			</button>
			<button class="btn btn-default btn-xs" title="{{$eduline}}" onclick="insertbbcomment('{{$comment}}','u', 'desc'); return false;">
				<i class="icon-underline comment-icon"></i>
			</button>
			<button class="btn btn-default btn-xs" title="{{$edquote}}" onclick="insertbbcomment('{{$comment}}','quote','desc'); return false;">
				<i class="icon-quote-left comment-icon"></i>
			</button>
			<button class="btn btn-default btn-xs" title="{{$edcode}}" onclick="insertbbcomment('{{$comment}}','code', 'desc'); return false;">
				<i class="icon-terminal comment-icon"></i>
			</button>
	</div>
						
	<div class='btn-group'>
		<button class="btn btn-default btn-xs" title="{{$edimg}}" onclick="insertbbcomment('{{$comment}}','img', 'desc'); return false;">
			<i class="icon-camera comment-icon"></i>
		</button>
		<button class="btn btn-default btn-xs" title="{{$edurl}}" onclick="insertbbcomment('{{$comment}}','url', 'desc'); return false;">
			<i class="icon-link comment-icon"></i>
		</button>
		<button class="btn btn-default btn-xs" title="{{$edvideo}}" onclick="insertbbcomment('{{$comment}}','video', 'desc'); return false;">
			<i class="icon-facetime-video comment-icon"></i>
		</button>
	</div>
	
	</div>
					
<!--	<div class="btn-group pull-right" id="comment-edit-submit-wrapper-desc">
	{{if $preview}}
	<button id="comment-edit-submit-desc" class="btn btn-default btn-xs" onclick="preview_comment(desc); return false;" title="{{$preview}}">
		<i class="icon-eye-open comment-icon" ></i>
	</button>
	{{/if}}
-->
</div>

<div class="clear"></div>

<div id="event-location-text">{{$l_text}}</div>

<textarea id="comment-edit-text-loc" class="comment-edit-text-full" name="location">{{$l_orig}}</textarea>

<div class="clear"></div>
<div id="comment-tools-loc" class="comment-tools" style="display: block;" >
	<div id="comment-edit-bb-loc" class="btn-toolbar pull-left">
		<div class='btn-group'>
			<button class="btn btn-default btn-xs" title="{{$edbold}}" onclick="insertbbcomment('{{$comment}}','b', 'loc'); return false;">
				<i class="icon-bold comment-icon"></i>
			</button>
			<button class="btn btn-default btn-xs" title="{{$editalic}}" onclick="insertbbcomment('{{$comment}}','i', 'loc'); return false;">
				<i class="icon-italic comment-icon"></i>
			</button>
			<button class="btn btn-default btn-xs" title="{{$eduline}}" onclick="insertbbcomment('{{$comment}}','u', 'loc'); return false;">
				<i class="icon-underline comment-icon"></i>
			</button>
			<button class="btn btn-default btn-xs" title="{{$edquote}}" onclick="insertbbcomment('{{$comment}}','quote','loc'); return false;">
				<i class="icon-quote-left comment-icon"></i>
			</button>
			<button class="btn btn-default btn-xs" title="{{$edcode}}" onclick="insertbbcomment('{{$comment}}','code', 'loc'); return false;">
				<i class="icon-terminal comment-icon"></i>
			</button>
	</div>
						
	<div class='btn-group'>
		<button class="btn btn-default btn-xs" title="{{$edimg}}" onclick="insertbbcomment('{{$comment}}','img', 'loc'); return false;">
			<i class="icon-camera comment-icon"></i>
		</button>
		<button class="btn btn-default btn-xs" title="{{$edurl}}" onclick="insertbbcomment('{{$comment}}','url', 'loc'); return false;">
			<i class="icon-link comment-icon"></i>
		</button>
		<button class="btn btn-default btn-xs" title="{{$edvideo}}" onclick="insertbbcomment('{{$comment}}','video', 'loc'); return false;">
			<i class="icon-facetime-video comment-icon"></i>
		</button>
	</div>
	
	</div>
					
<!--	<div class="btn-group pull-right" id="comment-edit-submit-wrapper-loc">
	{{if $preview}}
	<button id="comment-edit-submit-loc" class="btn btn-default btn-xs" onclick="preview_comment(loc); return false;" title="{{$preview}}">
		<i class="icon-eye-open comment-icon" ></i>
	</button>
	{{/if}}
-->
</div>



<br />
<div class="clear event-form-location-end"></div>


   <div class='field checkbox'>
   <label class="mainlabel" for='id_share'>{{$sh_text}}</label>
   <div><input type="checkbox" name='share' id='id_share' value="1" {{$sh_checked}} >
	<label class="switchlabel" for='id_share'> <span class="onoffswitch-inner" data-on='' data-off='' ></span>
	<span class="onoffswitch-switch"></span> </label></div><span class='field_help'></span>
    </div>


<div id="event-share-break"></div>


<button id="event-permissions-button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#aclModal" onclick="return false;">{{$permissions}}</button>
{{$acl}}

<div class="clear"></div>
<input id="event-submit" type="submit" name="submit" value="{{$submit}}" />
</form>

</div>
