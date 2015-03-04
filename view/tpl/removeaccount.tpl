<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$title}}</h2>
	</div>
	<div class="section-content-danger-wrapper" id="remove-account-desc">
		<strong>{{$desc.0}}</strong>{{$desc.1}}<strong>{{$desc.2}}</strong>
	</div>
	<div class="section-content-tools-wrapper">
		<form action="{{$basedir}}/removeaccount" autocomplete="off" method="post" >
			<input type="hidden" name="verify" value="{{$hash}}" />
			<div class="form-group" id="remove-account-pass-wrapper">
				<label id="remove-account-pass-label" for="remove-account-pass">{{$passwd}}</label>
				<input class="form-control" type="password" id="remove-account-pass" name="qxz_password" />
			</div>
			{{include file="field_checkbox.tpl" field=$global}}
			<button type="submit" name="submit" class="btn btn-danger">{{$submit}}</button>
		</form>
	</div>
</div>

