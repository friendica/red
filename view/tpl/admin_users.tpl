<script>
	function confirm_delete(uname){
		return confirm( "$confirm_delete".format(uname));
	}
	function confirm_delete_multi(){
		return confirm("$confirm_delete_multi");
	}
	function selectall(cls){
		$("."+cls).attr('checked','checked');
		return false;
	}
</script>
<div id='adminpage'>
	<h1>$title - $page</h1>
	
	<form action="$baseurl/admin/users" method="post">
        <input type='hidden' name='form_security_token' value='$form_security_token'>
		
		<h3>$h_pending</h3>
		{{ if $pending }}
			<table id='pending'>
				<thead>
				<tr>
					{{ for $th_pending as $th }}<th>$th</th>{{ endfor }}
					<th></th>
					<th></th>
				</tr>
				</thead>
				<tbody>
			{{ for $pending as $u }}
				<tr>
					<td class="created">$u.created</td>
					<td class="name">$u.name</td>
					<td class="email">$u.email</td>
					<td class="checkbox"><input type="checkbox" class="pending_ckbx" id="id_pending_$u.hash" name="pending[]" value="$u.hash" /></td>
					<td class="tools">
						<a href="$baseurl/regmod/allow/$u.hash" title='$approve'><span class='icon like'></span></a>
						<a href="$baseurl/regmod/deny/$u.hash" title='$deny'><span class='icon dislike'></span></a>
					</td>
				</tr>
			{{ endfor }}
				</tbody>
			</table>
			<div class='selectall'><a href='#' onclick="return selectall('pending_ckbx');">$select_all</a></div>
			<div class="submit"><input type="submit" name="page_users_deny" value="$deny"/> <input type="submit" name="page_users_approve" value="$approve" /></div>			
		{{ else }}
			<p>$no_pending</p>
		{{ endif }}
	
	
		
	
		<h3>$h_users</h3>
		{{ if $users }}
			<table id='users'>
				<thead>
				<tr>
					<th></th>
					{{ for $th_users as $th }}<th>$th</th>{{ endfor }}
					<th></th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				{{ for $users as $u }}
					<tr>
						<td><img src="$u.micro" alt="$u.nickname" title="$u.nickname"></td>
						<td class='email'>$u.account_email</td>
						<td class='register_date'>$u.account_created</td>
						<td class='login_date'>$u.account_lastlog</td>
						<td class='service_class'>$u.account_service_class</td>
						<td class="checkbox"><input type="checkbox" class="users_ckbx" id="id_user_$u.uid" name="user[]" value="$u.uid"/></td>
						<td class="tools">
							<a href="$baseurl/admin/users/block/$u.uid?t=$form_security_token" title='{{ if $u.blocked }}$unblock{{ else }}$block{{ endif }}'><span class='icon block {{ if $u.blocked==0 }}dim{{ endif }}'></span></a>
							<a href="$baseurl/admin/users/delete/$u.uid?t=$form_security_token" title='$delete' onclick="return confirm_delete('$u.name')"><span class='icon drop'></span></a>
						</td>
					</tr>
				{{ endfor }}
				</tbody>
			</table>
			<div class='selectall'><a href='#' onclick="return selectall('users_ckbx');">$select_all</a></div>
			<div class="submit"><input type="submit" name="page_users_block" value="$block/$unblock" /> <input type="submit" name="page_users_delete" value="$delete" onclick="return confirm_delete_multi()" /></div>						
		{{ else }}
			NO USERS?!?
		{{ endif }}
	</form>
</div>
