<h2>$title</h2>

<form action="zchannel" method="post" id="zchannel-form">

	<div id="zchannel-desc" class="descriptive-paragraph">$desc</div>

	<label for="zchannel-name" id="label-zchannel-name" class="zchannel-label" >$label_name</label>
	<input type="text" name="name" id="zchannel-name" class="zchannel-input" value="$name" />
	<div id="zchannel-name-feedback" class="zchannel-feedback"></div>
	<div id="zchannel-name-end"  class="zchannel-field-end"></div>

	<div id="zchannel-name-help" class="descriptive-paragraph">$help_name</div>

	<label for="zchannel-nickname" id="label-zchannel-nickname" class="zchannel-label" >$label_nick</label>
	<input type="text" name="nickname" id="zchannel-nickname" class="zchannel-input" value="$nickname" />
	<div id="zchannel-nickname-feedback" class="zchannel-feedback"></div>
	<div id="zchannel-nickname-end"  class="zchannel-field-end"></div>

	<div id="zchannel-nick-desc" class="descriptive-paragraph">$nick_desc</div>


	<input type="checkbox" name="import" id="zchannel-import" value="1" />
	<label for="zchannel-import" id="label-zchannel-import">$label_import</label>
	<div id="zchannel-import-end" class="zchannel-field-end"></div>

	<input type="submit" name="submit" id="zchannel-submit-button" value="$submit" />
	<div id="zchannel-submit-end" class="zchannel-field-end"></div>

</form>
