<h2>{{$title}}</h2>

<form action="new_channel" method="post" id="newchannel-form" class="stylish-select">

	<div id="newchannel-desc" class="descriptive-paragraph">{{$desc}}</div>

	<div id="newchannel-role-help" class="descriptive-paragraph">{{$help_role}}</div>

	<label for="newchannel-role" id="label-newchannel-role" class="newchannel-label" >{{$label_role}}</label>
	{{$role_select}}
	<div class="newchannel-role-morehelp"><a href="help/roles" title="{{$what_is_role}}" target="_blank">{{$questionmark}}</a></div>
	<div id="newchannel-role-end"  class="newchannel-field-end"></div>


	<label for="newchannel-name" id="label-newchannel-name" class="newchannel-label" >{{$label_name}}</label>
	<input type="text" name="name" id="newchannel-name" class="newchannel-input" value="{{$name}}" />
	<div id="name-spinner"></div>
	<div id="newchannel-name-feedback" class="newchannel-feedback"></div>
	<div id="newchannel-name-end"  class="newchannel-field-end"></div>

	<div id="newchannel-name-help" class="descriptive-paragraph">{{$help_name}}</div>

	<label for="newchannel-nickname" id="label-newchannel-nickname" class="newchannel-label" >{{$label_nick}}</label>
	<input type="text" name="nickname" id="newchannel-nickname" class="newchannel-input" value="{{$nickname}}" />
	<div id="nick-spinner"></div>
	<div id="newchannel-nickname-feedback" class="newchannel-feedback"></div>
	<div id="newchannel-nickname-end"  class="newchannel-field-end"></div>

	<div id="newchannel-nick-desc" class="descriptive-paragraph">{{$nick_desc}}</div>


	<div id="newchannel-import-link" class="descriptive-paragraph" >{{$label_import}}</div>

	<div id="newchannel-import-end" class="newchannel-field-end"></div>

	<input type="submit" name="submit" id="newchannel-submit-button" value="{{$submit}}" />
	<div id="newchannel-submit-end" class="newchannel-field-end"></div>

</form>
