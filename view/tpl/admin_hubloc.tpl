<div class="generic-content-wrapper" id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/admin/hubloc" method="post">
	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
	
	<table id='server'>
		<thead>
			<tr>
			{{foreach $th_hubloc as $th}}<th>{{$th}}</th>{{/foreach}}
			</tr>
		</thead>
		<tbody>
			
			{{foreach $hubloc as $hub}}<tr>
			<td>{{$hub.hubloc_id}}</td><td>{{$hub.hubloc_addr}}</td><td>{{$hub.hubloc_host}}</td><td>{{$hub.hubloc_status}}</td>
			</tr>{{/foreach}}
		</tbody>
	</table>


</div>
