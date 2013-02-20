<h2>$title</h2>

<form action="new_channel" method="post" id="newchannel-form">

	<div id="newchannel-desc" class="descriptive-paragraph">$desc</div>

	<label for="newchannel-name" id="label-newchannel-name" class="newchannel-label" >$label_name</label>
	<input type="text" name="name" id="newchannel-name" class="newchannel-input" value="$name" />
	<div id="newchannel-name-feedback" class="newchannel-feedback"></div>
	<div id="newchannel-name-end"  class="newchannel-field-end"></div>

	<div id="newchannel-name-help" class="descriptive-paragraph">$help_name</div>

	<label for="newchannel-nickname" id="label-newchannel-nickname" class="newchannel-label" >$label_nick</label>
	<input type="text" name="nickname" id="newchannel-nickname" class="newchannel-input" value="$nickname" />
	<div id="newchannel-nickname-feedback" class="newchannel-feedback"></div>
	<div id="newchannel-nickname-end"  class="newchannel-field-end"></div>

	<div id="newchannel-nick-desc" class="descriptive-paragraph">$nick_desc</div>


	<div id="newchannel-import-link" class="descriptive-paragraph" >$label_import</div>

	<div id="newchannel-import-end" class="newchannel-field-end"></div>

	<input type="submit" name="submit" id="newchannel-submit-button" value="$submit" />
	<div id="newchannel-submit-end" class="newchannel-field-end"></div>

</form>
