{{* TODO: Make id configurabel *}}
<select id='timezone_select' name='timezone'>
{{foreach $continents as $continent => $cities}}
<optgroup label="{{$continent}}">
{{foreach $cities as $city => $value}}
<option value='{{$value}}' {{if $value == $selected}}selected='selected'{{/if}}>{{$city}}</option>
{{/foreach}}
</optgroup>
{{/foreach}}
</select>

