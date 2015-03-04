<div class="generic-content-wrapper-styled">
<h1>{{$title}}</h1>

<form action="settings/featured" method="post" autocomplete="off">
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

{{if $diaspora_enabled}}
<div class="settings-block">
<button class="btn btn-default" data-target="#settings-dspr-wrapper" data-toggle="collapse" type="button">{{$dsprtitle}}</button>
<div id="settings-dspr-wrapper" class="collapse well">
<div id="dspr-settings-wrapper">
<label id="dspr-pubcomment-label" for="dspr-pubcomment-checkbox">{{$dsprhelp}}</label>
<input id="dspr-pubcomment-checkbox" type="checkbox" name="dspr_pubcomment" value="1" ' . {{if $pubcomments}} checked="checked" {{/if}} />
<div class="clear"></div>
<label id="dspr-hijack-label" for="dspr-hijack-checkbox">{{$dsprhijack}}</label>
<input id="dspr-hijack-checkbox" type="checkbox" name="dspr_hijack" value="1" ' . {{if $hijacking}} checked="checked" {{/if}} />
<div class="clear"></div>
</div>



<div class="settings-submit-wrapper" ><input type="submit" name="dspr-submit" class="settings-submit" value="{{$dsprsubmit}}" /></div></div></div>
{{/if}}
{{$settings_addons}}

</form>

</div>
