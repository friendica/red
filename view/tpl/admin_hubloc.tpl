<div class="generic-content-wrapper-styled" id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>

	<table id='server'>
		<thead>
			<tr>
			{{foreach $th_hubloc as $th}}<th>{{$th}}</th>{{/foreach}}
			</tr>
		</thead>
		<tbody>
			
			{{foreach $hubloc as $hub}}<tr>
			<td>{{$hub.hubloc_id}}</td><td>{{$hub.hubloc_addr}}</td><td>{{$hub.hubloc_host}}</td><td>{{$hub.hubloc_status}}</td>
			<td>
			<form action="{{$baseurl}}/admin/hubloc" method="post">
			<input type="hidden" name="hublocid" value="{{$hub.hubloc_id}}">
			<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
			<input type='hidden' name='url' value='{{$hub.hubloc_host}}'>
			<input type="submit" name="check" value="check" >
			<input type="submit" name="repair" value="repair" ></td>
			</form>
			</tr>{{/foreach}}
		</tbody>
	</table>


</div>
