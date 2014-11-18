<h3>{{$header}}</h3>

<div id="prvmail-wrapper" >
<form id="prvmail-form" action="mail" method="post" >

{{$parent}}

<div id="prvmail-to-label">{{$to}}</div>

{{if $showinputs}}
<input type="text" id="recip" name="messagerecip" value="{{$prefill}}" maxlength="255" size="64" tabindex="10" />
<input type="hidden" id="recip-complete" name="messageto" value="{{$preid}}">
{{else}}
{{$select}}
{{/if}}

<input type="hidden" id="inp-prvmail-expires" name="expires" value="{{$defexpire}}" />
<input type="hidden" name="media_str" id="jot-media" value="" />

<div id="prvmail-subject-label">{{$subject}}</div>
<input type="text" size="64" maxlength="255" id="prvmail-subject" name="subject" value="{{$subjtxt}}" {{$readonly}} tabindex="11" />

<div id="prvmail-message-label">{{$yourmessage}}</div>
<textarea rows="8" cols="72" class="prvmail-text" id="prvmail-text" name="body" tabindex="12">{{$text}}</textarea>


<div id="prvmail-submit-wrapper" >
	<input type="submit" id="prvmail-submit" name="submit" value="{{$submit}}" tabindex="13" />
	<button id="prvmail-upload-wrapper" class="btn btn-default btn-sm" >
		<i id="prvmail-upload" class="icon-camera jot-icons" title="{{$upload}}"></i>
	</button> 

	<button id="prvmail-attach-wrapper" class="btn btn-default btn-sm" >
		<i id="prvmail-attach" class="icon-paper-clip jot-icons" title="{{$attach}}"></i>
	</button> 

	<button id="prvmail-link-wrapper" class="btn btn-default btn-sm" onclick="prvmailJotGetLink(); return false;" >
		<i id="prvmail-link" class="icon-link jot-icons" title="{{$insert}}" ></i>
	</button> 
	{{if $feature_expire}}
	<button id="prvmail-expire-wrapper" class="btn btn-default btn-sm" onclick="prvmailGetExpiry();return false;" >
		<i id="prvmail-expires" class="icon-eraser jot-icons" title="{{$expires}}" ></i>
	</button>
	{{/if}}
	{{if $feature_encrypt}}
	<button id="prvmail-encrypt-wrapper" class="btn btn-default btn-sm" onclick="red_encrypt('{{$cipher}}','#prvmail-text',$('#prvmail-text').val());return false;">
		<i id="prvmail-encrypt" class="icon-key jot-icons" title="{{$encrypt}}" ></i>
	</button> 
	{{/if}}
	<div id="prvmail-rotator-wrapper" >
		<div id="prvmail-rotator"></div>
	</div> 
</div>
<div id="prvmail-end"></div>
</form>
</div>
