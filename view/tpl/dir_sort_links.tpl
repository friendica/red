<div class="widget" id="dir_sort_links">
<h3>{{$header}}</h3>

{{include file="field_checkbox.tpl" field=$safemode}}
{{include file="field_checkbox.tpl" field=$globaldir}}
{{include file="field_checkbox.tpl" field=$pubforums}}

{{$sort}}: <select onchange='window.location.href="{{$sorturl}}&order="+this.value'>
<option value='normal' {{if $selected_sort == 'normal'}}selected='selected'{{/if}}>{{$normal}}</option>
<option value='reverse' {{if $selected_sort == 'reverse'}}selected='selected'{{/if}}>{{$reverse}}</option>
<option value='date' {{if $selected_sort == 'date'}}selected='selected'{{/if}}>{{$date}}</option>
<option value='reversedate' {{if $selected_sort == 'reversedate'}}selected='selected'{{/if}}>{{$reversedate}}</option>
</select><br />

</div>
