<h3>{{$header}}</h3>

<div class="rating-target-name">{{if $site}}{{$website}} {{$site}}{{else}}{{$tgt_name}}{{/if}}</div>

<h3>{{$lbl_rating}}</h3>

<form action="rate" method="post">

{{$rating}}

<input type="hidden" name="execute" value="1" />
<input type="hidden" name="target" value="{{$target}}" />

<input id="contact-rating-mirror" type="hidden" name="rating" value="{{$rating_val}}" />
<h3 class="abook-rating-text-desc">{{$lbl_rating_txt}}</h3>
<textarea name="rating_text" id="rating-text" >{{$rating_txt}}</textarea>

<div class="clear"></div>

<input class="contact-edit-submit" type="submit" name="done" value="{{$submit}}" />

</form>