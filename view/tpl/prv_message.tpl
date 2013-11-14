<h3>{{$header}}</h3>

<div id="prvmail-wrapper" >
<form id="prvmail-form" action="message" method="post" >

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
	<div id="prvmail-upload-wrapper" >
		<i id="prvmail-upload" class="icon-camera jot-icons" title="{{$upload}}"></i>
	</div> 

	<div id="prvmail-attach-wrapper" >
		<i id="prvmail-attach" class="icon-paper-clip jot-icons" title="{{$attach}}"></i>
	</div> 

	<div id="prvmail-link-wrapper" >
		<i id="prvmail-link" class="icon-link jot-icons" title="{{$insert}}" onclick="jotGetLink(); return false;"></i>
	</div> 

    <div id="prvmail-expire-wrapper" style="display: {{$feature_expire}};" >
        <i id="prvmail-expires" class="icon-eraser jot-icons" title="{{$expires}}" onclick="prvmailGetExpiry();return false;"></i>
    </div>
	<div id="prvmail-encrypt-wrapper" style="display: {{$feature_encrypt}};" >
		<i id="prvmail-encrypt" class="icon-key jot-icons" title="{{$encrypt}}" onclick="red_encrypt('{{$cipher}}','#prvmail-text',$('#prvmail-text').val());return false;"></i>
	</div> 

	<div id="prvmail-rotator-wrapper" >
		<img id="prvmail-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" />
	</div> 
</div>
<div id="prvmail-end"></div>
</form>
</div>
