
<div class="intro-wrapper" id="intro-$contact_id" >

<div class="intro-fullname" id="intro-fullname-$contact_id" >$fullname</div>
<a class="intro-url-link" id="intro-url-link-$contact_id" href="$url" ><img id="photo-$contact_id" class="intro-photo" src="$photo" width="175" height=175" title="$fullname" alt="$fullname" /></a>
<div class="intro-wrapper-end" id="intro-wrapper-end-$contact_id"></div>
<form class="intro-form" action="intro" method="post">
<input class="intro-submit-ignore" type="submit" name="submit" value="$ignore" />
<input class="intro-submit-block" type="submit" name="submit" value="$block" />
<input class="intro-submit-discard" type="submit" name="submit" value="$discard" />
{{inc field_checkbox.tpl with $field=$hidden }}{{endinc}}
{# {{ inc field_checkbox.tpl with $field=$activity }}{{endinc}} #}
<input type="hidden" name="contact_id" value="$contact_id" >

<input class="intro-submit-approve" type="submit" name="submit" value="$approve" />
</form>
</div>
<div class="intro-end"></div>
