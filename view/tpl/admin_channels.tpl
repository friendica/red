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
<div class = "generic-content-wrapper" id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>
	
	<form action="{{$baseurl}}/admin/channels" method="post">
        <input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
		
		<h3>{{$h_users}}</h3>
		{{if $users}}
			<table id='channels'>
				<thead>
				<tr>
					{{foreach $th_users as $th}}<th>{{$th}}</th>{{/foreach}}
					<th></th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				{{foreach $users as $u}}
					<tr>
						<td class='channel_id'>{{$u.channel_id}}</td>
						<td class='channel_name'>{{$u.channel_name}}</td>
						<td class='channel_address'>{{$u.channel_address}}</td>
						<td class="checkbox"><input type="checkbox" class="users_ckbx" id="id_user_{{$u.account_id}}" name="user[]" value="{{$u.account_id}}"/></td>
						<td class="tools">
							<a href="{{$baseurl}}/admin/users/block/{{$u.account_id}}?t={{$form_security_token}}" title='{{if ($u.blocked)}}{{$unblock}}{{else}}{{$block}}{{/if}}'><i class='icon-ban-circle admin-icons {{if ($u.blocked)}}dim{{/if}}'></i></a>
							<a href="{{$baseurl}}/admin/users/delete/{{$u.account_id}}?t={{$form_security_token}}" title='{{$delete}}' onclick="return confirm_delete('{{$u.name}}')"><i class='icon-remove admin-icons'></i></a>
						</td>
					</tr>
				{{/foreach}}
				</tbody>
			</table>
			<div class='selectall'><a href='#' onclick="return selectall('users_ckbx');">{{$select_all}}</a></div>
			<div class="submit"><input type="submit" name="page_users_block" value="{{$block}}/{{$unblock}}" /> <input type="submit" name="page_channels_delete" value="{{$delete}}" onclick="return confirm_delete_multi()" /></div>						
		{{else}}
			NO USERS?!?
		{{/if}}
	</form>
</div>
