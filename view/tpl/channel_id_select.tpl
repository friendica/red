{{if $channels}}
<select name="dest_channel" id="dest-channel-select"> 
{{foreach $channels as $c}}
<option {{if $c.channel_id == $selected}}selected="selected"{{/if}} value="{{$c.channel_id}}">{{$c.channel_name}}</option>
{{/foreach}}
</select>
{{/if}}