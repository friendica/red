<div class="generic-content-wrapper">
<h1>{{$title}}</h1>


<form action="settings/oauth" method="post" autocomplete="off">
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	<div id="profile-edit-links">
		<ul>
			<li>
				<a id="profile-edit-view-link" href="{{$baseurl}}/settings/oauth/add">{{$add}}</a>
			</li>
		</ul>
	</div>

	{{foreach $apps as $app}}
	<div class='oauthapp'>
		<img src='{{$app.icon}}' class="{{if $app.icon}} {{else}}noicon{{/if}}">
		{{if $app.name}}<h4>{{$app.name}}</h4>{{else}}<h4>{{$noname}}</h4>{{/if}}
		{{if $app.my}}
			{{if $app.oauth_token}}
			<div class="settings-submit-wrapper" ><button class="settings-submit"  type="submit" name="remove" value="{{$app.oauth_token}}">{{$remove}}</button></div>
			{{/if}}
		{{/if}}
		{{if $app.my}}
		<a href="{{$baseurl}}/settings/oauth/edit/{{$app.client_id}}" title="{{$edit}}"><i class="icon-pencil"></i></a>
		<a href="{{$baseurl}}/settings/oauth/delete/{{$app.client_id}}?t={{$form_security_token}}" title="{{$delete}}"><i class="icon-remove"></i></a>
		{{/if}}		
	</div>
	{{/foreach}}

</form>
</div>
