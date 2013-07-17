<h2>{{$title}}</h2>

<form action="import" method="post" id="import-channel-form">

	<div id="import-desc" class="descriptive-paragraph">{{$desc}}</div>

	<label for="import-filename" id="label-import-filename" class="import-label" >{{$label_filename}}</label>
	<input type="file" name="filename" id="import-filename" class="import-input" value="" />
	<div id="import-filename-end" class="import-field-end"></div>

	<div id="import-choice" class="descriptive-paragraph">{{$choice}}</div>

	<label for="import-old-address" id="label-import-old-address" class="import-label" >{{$label_old_address}}</label>
	<input type="text" name="old_address" id="import-old-address" class="import-input" value="" />
	<div id="import-old-address-end" class="import-field-end"></div>

	<label for="import-old-email" id="label-import-old-email" class="import-label" >{{$label_old_email}}</label>
	<input type="text" name="email" id="import-old-email" class="import-input" value="{{$email}}" />
	<div id="import-old-email-end" class="import-field-end"></div>

	<label for="import-old-pass" id="label-import-old-pass" class="import-label" >{{$label_old_pass}}</label>
	<input type="password" name="password" id="import-old-pass" class="import-input" value="{{$pass}}" />
	<div id="import-old-pass-end" class="import-field-end"></div>

	<div id="import-common-desc" class="descriptive-paragraph">{{$common}}</div>

	<input type="checkbox" name="make_primary" id="import-make-primary" value="1" />
	<label for="import-make-primary" id="label-import-make-primary">{{$label_import_primary}}</label>
	<div id="import-make-primary-end" class="import-field-end"></div>

	<input type="submit" name="submit" id="import-submit-button" value="{{$submit}}" />
	<div id="import-submit-end" class="import-field-end"></div>

</form>

