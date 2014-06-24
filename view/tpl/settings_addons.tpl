<div class="generic-content-wrapper">
<h1>{{$title}}</h1>


<form action="settings/featured" method="post" autocomplete="off">
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

{{$settings_addons}}

</form>

</div>
