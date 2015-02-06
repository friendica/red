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

<input type="checkbox" name="nofinish" value="1" id="event-nofinish-checkbox" {{$n_checked}} onclick="showHideFinishDate(); return true;" /> <div id="event-nofinish-text">{{$n_text}}</div>

<div id="event-nofinish-break"></div>

<div id="event-finish-wrapper">
<div id="event-finish-text">{{$f_text}}</div>
{{$f_dsel}}
</div>

<div id="event-datetime-break"></div>


<input type="checkbox" name="adjust" value="1" id="event-adjust-checkbox" {{$a_checked}} /> <div id="event-adjust-text">{{$a_text}}</div>

<div id="event-adjust-break"></div>



{{if $catsenabled}}
<div id="event-category-wrap">
	<input name="category" id="event-category" type="text" placeholder="{{$placeholdercategory}}" value="{{$category}}" class="event-cats" style="display: block;" data-role="tagsinput"  />
</div>
{{/if}}



<div id="event-desc-text">{{$d_text}}</div>
<textarea id="event-desc-textarea" name="desc">{{$d_orig}}</textarea>


<div id="event-location-text">{{$l_text}}</div>
<textarea id="event-location-textarea" name="location">{{$l_orig}}</textarea>
<br />

<input type="checkbox" name="share" value="1" id="event-share-checkbox" {{$sh_checked}} /> <div id="event-share-text">{{$sh_text}}</div>
<div id="event-share-break"></div>

<button id="event-permissions-button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#aclModal" onclick="return false;">{{$permissions}}</button>
	{{$acl}}

<div class="clear"></div>
<input id="event-submit" type="submit" name="submit" value="{{$submit}}" />
</form>

</div>
