<script>
	function confirm_delete(uname){
		return confirm( "{{$confirm_delete}}".format(uname));
	}
	function confirm_delete_multi(){
		return confirm("{{$confirm_delete_multi}}");
	}
	function selectall(cls){
		$("."+cls).attr('checked','checked');
		return false;
	}
</script>
<div class = "generic-content-wrapper-styled" id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>
	
	<form action="{{$baseurl}}/admin/channels" method="post">
        <input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
		
		<h3>{{$h_channels}}</h3>
		{{if $channels}}
			<table id='channels'>
				<thead>
				<tr>
					{{foreach $th_channels as $th}}<th>{{$th}}</th>{{/foreach}}
					<th></th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				{{foreach $channels as $c}}
					<tr>
						<td class='channel_id'>{{$c.channel_id}}</td>
						<td class='channel_name'><a href="channel/{{$c.channel_address}}">{{$c.channel_name}}</a></td>
						<td class='channel_address'>{{$c.channel_address}}</td>
						<td class="checkbox"><input type="checkbox" class="channels_ckbx" id="id_channel_{{$c.channel_id}}" name="channel[]" value="{{$c.channel_id}}"/></td>
						<td class="tools">
							<a href="{{$baseurl}}/admin/channels/block/{{$c.channel_id}}?t={{$form_security_token}}" class="btn btn-default btn-xs" title='{{if ($c.blocked)}}{{$unblock}}{{else}}{{$block}}{{/if}}'><i class='icon-ban-circle admin-icons {{if ($c.blocked)}}dim{{/if}}'></i></a>
							<a href="{{$baseurl}}/admin/channels/delete/{{$c.channel_id}}?t={{$form_security_token}}" class="btn btn-default btn-xs" title='{{$delete}}' onclick="return confirm_delete('{{$c.channel_name}}')"><i class='icon-trash admin-icons'></i></a>
						</td>
					</tr>
				{{/foreach}}
				</tbody>
			</table>
			<div class='selectall'><a href='#' onclick="return selectall('channels_ckbx');">{{$select_all}}</a></div>
			<div class="submit"><input type="submit" name="page_channels_block" value="{{$block}}/{{$unblock}}" /> <input type="submit" name="page_channels_delete" value="{{$delete}}" onclick="return confirm_delete_multi()" /></div>						
		{{else}}
			NO CHANNELS?!?
		{{/if}}
	</form>
</div>
