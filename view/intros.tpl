
<div class="intro-wrapper" id="intro-$contact-id" >
<hr class="intro-top" />
<p class="intro-desc">Notification type: Introduction</p>
<div class="intro-fullname" id="intro-fullname-$contact-id" >$fullname</div>
<a class="intro-url-link" id="intro-url-link-$contact-id" href="$url" ><img id="photo-$contact-id" class="intro-photo" src="$photo" width="175" height=175" name="$fullname" alt="fullname" /></a>
<div class="intro-knowyou">Presumably known to you? <strong>$knowyou</strong></div>
<div class="intro-note" id="intro-note-$contact-id">$note</div>
<div class="intro-wrapper-end" id="intro-wrapper-end-$contact-id"></div>

<form class="intro-approve-form" action="dfrn_confirm" method="post">
<input type="hidden" name="dfrn_id" value="$dfrn-id" >
<input type="hidden" name="intro_id" value="$intro_id" >
<input class="intro-submit-approve" type="submit" name="submit" value="Approve" />
</form>
<form class="intro-form" action="notifications/$intro_id" method="post">
<input class="intro-submit-ignore" type="submit" name="submit" value="Ignore" />
<input class="intro-submit-discard" type="submit" name="submit" value="Discard" />
</form>
</div>
<div class="intro-end"></div>
