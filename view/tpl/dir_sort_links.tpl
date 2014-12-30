<div class="widget" id="dir_sort_links">
<h3>{{$header}}</h3>
{{$sort}}: <select onchange='window.location.href="{{$sorturl}}&order="+this.value'>
<option value='normal' {{if $selected_sort == 'normal'}}selected='selected'{{/if}}>{{$normal}}</option>
<option value='reverse' {{if $selected_sort == 'reverse'}}selected='selected'{{/if}}>{{$reverse}}</option>
<option value='date' {{if $selected_sort == 'date'}}selected='selected'{{/if}}>{{$date}}</option>
<option value='reversedate' {{if $selected_sort == 'reversedate'}}selected='selected'{{/if}}>{{$reversedate}}</option>
</select><br />
<input type='checkbox' {{if $pubforumsonly}}checked='checked'{{/if}} onchange='window.location.href="{{$forumsurl}}&pubforums="+(this.checked ? 1 : 0)'/> {{$pubforums}}<br />
</div>
