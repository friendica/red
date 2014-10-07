<h3>{{$title}}</h3>

<p>
{{if ! $bootstrap}}
{{$format_desc}} {{/if}}{{$desc}}
</p>

<form action="{{$post}}" method="post" >

<input type="hidden" name="event_id" value="{{$eid}}" />
<input type="hidden" name="xchan" value="{{$xchan}}" />
<input type="hidden" name="mid" value="{{$mid}}" />

<div id="event-summary-text">{{$t_text}}</div>
<input type="text" id="event-summary" name="summary" value="{{$t_orig}}" />

<div id="event-start-text">{{$s_text}}</div>
{{if $bootstrap}}
<i class="icon-calendar btn btn-default" onclick="eventGetStart(); return false;" /></i> <input type="text" name="start_text" id="start-text" value="{{$stext}}" />
{{else}}
{{$s_dsel}} {{$s_tsel}}
{{/if}}

<div class="clear"></div><br />

<input type="checkbox" name="nofinish" value="1" id="event-nofinish-checkbox" {{$n_checked}} onclick="showHideFinishDate(); return true;" /> <div id="event-nofinish-text">{{$n_text}}</div>

<div id="event-nofinish-break"></div>

<div id="event-finish-wrapper">
<div id="event-finish-text">{{$f_text}}</div>
{{if $bootstrap}}
<i class="icon-calendar btn btn-default" onclick="eventGetFinish(); return false;" /></i> <input type="text" name="finish_text" id="finish-text" value="{{$ftext}}" />
{{else}}
{{$f_dsel}} {{$f_tsel}}
{{/if}}
</div>

<div id="event-datetime-break"></div>


<input type="checkbox" name="adjust" value="1" id="event-adjust-checkbox" {{$a_checked}} /> <div id="event-adjust-text">{{$a_text}}</div>

<div id="event-adjust-break"></div>



{{if $catsenabled}}
<div id="event-category-wrap">
	<input name="category" id="event-category" type="text" placeholder="{{$placeholdercategory}}" value="{{$category}}" class="event-cats" style="display: block;" />
</div>
{{/if}}



<div id="event-desc-text">{{$d_text}}</div>
<textarea id="event-desc-textarea" name="desc">{{$d_orig}}</textarea>


<div id="event-location-text">{{$l_text}}</div>
<textarea id="event-location-textarea" name="location">{{$l_orig}}</textarea>
<br />

<input type="checkbox" name="share" value="1" id="event-share-checkbox" {{$sh_checked}} /> <div id="event-share-text">{{$sh_text}}</div>
<div id="event-share-break"></div>

{{$acl}}

<div class="clear"></div>
<input id="event-submit" type="submit" name="submit" value="{{$submit}}" />
</form>

<!-- Modal for item expiry-->
<div class="modal" id="startModal" tabindex="-1" role="dialog" aria-labelledby="expiryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="startModalLabel">{{$s_text}}</h4>
      </div>
     <!--  <div class="modal-body"> -->
            <div class="modal-body form-group" style="width:90%">
                <div class="input-group input-group-sm date" id="datetimepickerstart">
                    <span class="input-group-addon"><!-- <span class="glyphicon glyphicon-calendar"></span> -->
                    <span class="icon-calendar"></span>
                    </span>
                    <input id="start-date" value='{{$stext}}' type='text' class="form-control" data-date-format="YYYY-MM-DD HH:mm" size="20"/>
                </div>
            </div>
      <!-- </div> -->
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{{$ModalCANCEL}}</button>
        <button id="start-modal-OKButton" type="button" class="btn btn-primary">{{$ModalOK}}</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script type="text/javascript">
  $(function() {
    $('#datetimepickerstart').datetimepicker({
      language: 'us',
      icons: {
					time: "icon-time",
					date: "icon-calendar",
					up: "icon-arrow-up",
					down: "icon-arrow-down"
				}
    });
  });
</script>

<!-- Modal for item expiry-->
<div class="modal" id="finishModal" tabindex="-1" role="dialog" aria-labelledby="expiryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="finishModalLabel">{{$s_text}}</h4>
      </div>
     <!--  <div class="modal-body"> -->
            <div class="modal-body form-group" style="width:90%">
                <div class="input-group input-group-sm date" id="datetimepickerfinish">
                    <span class="input-group-addon"><!-- <span class="glyphicon glyphicon-calendar"></span> -->
                    <span class="icon-calendar"></span>
                    </span>
                    <input id="finish-date" value='{{$ftext}}' type='text' class="form-control" data-date-format="YYYY-MM-DD HH:mm" size="20"/>
                </div>
            </div>
      <!-- </div> -->
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{{$ModalCANCEL}}</button>
        <button id="finish-modal-OKButton" type="button" class="btn btn-primary">{{$ModalOK}}</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script type="text/javascript">
  $(function() {
    $('#datetimepickerfinish').datetimepicker({
      language: 'us',
      icons: {
					time: "icon-time",
					date: "icon-calendar",
					up: "icon-arrow-up",
					down: "icon-arrow-down"
				}
    });
  });
</script>

