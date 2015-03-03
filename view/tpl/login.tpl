<form action="{{$dest_url}}" id="{{$form_id}}" method="post" >
	<input type="hidden" name="auth-params" value="login" />
	<div id="login-main">
		<div id="login-input" class="form-group">
			{{include file="field_input.tpl" field=$lname}}
			{{include file="field_password.tpl" field=$lpassword}}
			{{include file="field_checkbox.tpl" field=$remember}}
			<button type="submit" name="submit" class="btn btn-block btn-primary">{{$login}}</button>
		</div>
		<div id="login-extra-links">
			{{if $register}}<a href="{{$register.link}}" title="{{$register.title}}" id="register-link" class="pull-right">{{$register.desc}}</a>{{/if}}
			<a href="lostpass" title="{{$lostpass}}" id="lost-password-link" >{{$lostlink}}</a>
		</div>
	</div>
	{{foreach $hiddens as $k=>$v}}
		<input type="hidden" name="{{$k}}" value="{{$v}}" />
	{{/foreach}}
</form>
<script type="text/javascript"> $(document).ready(function() { $("#id_{{$lname.0}}").focus();} );</script>
