<div class="generic-content-wrapper-styled">
<h1>{{$title}}</h1>

<form action="settings/connectors" method="post" autocomplete="off">
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

{{$settings_connectors}}

</form>
</div>
