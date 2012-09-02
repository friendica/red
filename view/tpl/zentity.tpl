<h2>$title</h2>

<form action="zentity" method="post" id="zentity-form">

	<div id="zentity-desc" class="descriptive-paragraph">$desc</div>

	<label for="zentity-name" id="label-zentity-name" class="zentity-label" >$label_name</label>
	<input type="text" name="name" id="zentity-name" class="zentity-input" value="$name" />
	<div id="zentity-name-feedback" class="zentity-feedback"></div>
	<div id="zentity-name-end"  class="zentity-field-end"></div>

	<div id="zentity-name-help" class="descriptive-paragraph">$help_name</div>

	<label for="zentity-nickname" id="label-zentity-nickname" class="zentity-label" >$label_nick</label>
	<input type="text" name="nickname" id="zentity-nickname" class="zentity-input" value="$nickname" />
	<div id="zentity-nickname-feedback" class="zentity-feedback"></div>
	<div id="zentity-nickname-end"  class="zentity-field-end"></div>

	<div id="zentity-nick-desc" class="descriptive-paragraph">$nick_desc</div>


	<input type="checkbox" name="import" id="zentity-import" value="1" />
	<label for="zentity-import" id="label-zentity-import">$label_import</label>
	<div id="zentity-import-end" class="zentity-field-end"></div>

	<input type="submit" name="submit" id="zentity-submit-button" value="$submit" />
	<div id="zentity-submit-end" class="zentity-field-end"></div>

</form>
