
<div class="intro-wrapper" id="intro-$contact-id" >

<p class="intro-desc">Notification type: Introduction</p>
<div class="intro-fullname" id="intro-fullname-$contact-id" >$fullname</div>
<a class="intro-url-link" id="intro-url-link-$contact-id" href="$url" ><img id="photo-$contact-id" class="intro-photo" src="$photo" width="175" height=175" name="$fullname" alt="fullname" /></a>
<div class="intro-knowyou">Presumably known to you? <strong>$knowyou</strong></div>
<div class="intro-note" id="intro-note-$contact-id">$note</div>
<div class="intro-wrapper-end" id="intro-wrapper-end-$contact-id"></div>
<form class="intro-form" action="notifications/$intro_id" method="post">
<input class="intro-submit-ignore" type="submit" name="submit" value="Ignore" />
<input class="intro-submit-discard" type="submit" name="submit" value="Discard" />
</form>
<div class="intro-form-end"></div>

<form class="intro-approve-form" action="dfrn_confirm" method="post">
<input type="hidden" name="dfrn_id" value="$dfrn-id" >
<input type="hidden" name="intro_id" value="$intro_id" >

<div class="intro-approve-as-friend-desc">Approve as: </div>

<div class="intro-approve-as-friend-wrapper">
	<label class="intro-approve-as-friend-label" for="intro-approve-as-friend-$intro_id">Friend</label>
	<input type="radio" name="duplex" id="intro-approve-as-friend-$intro_id" class="intro-approve-as-friend" checked="checked" value="1" />
	<div class="intro-approve-friend-break" ></div>	
</div>
<div class="intro-approve-as-friend-end"></div>
<div class="intro-approve-as-fan-wrapper">
	<label class="intro-approve-as-fan-label" for="intro-approve-as-fan-$intro_id">Fan/Admirer</label>
	<input type="radio" name="duplex" id="intro-approve-as-fan-$intro_id" class="intro-approve-as-fan" $fan_selected value="0"  />
	<div class="intro-approve-fan-break"></div>
</div>
<div class="intro-approve-as-end"></div>

<input class="intro-submit-approve" type="submit" name="submit" value="Approve" />
</form>
</div>
<div class="intro-end"></div>
