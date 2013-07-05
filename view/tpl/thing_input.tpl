<h2>{{$thing_hdr}}</h2>
<form action="thing" method="post" >

{{if $multiprof }}
<div class="thing-profile-label">{{$profile_lbl}}</div>

<div class="thing-profile">{{$profile_select}}</div>
{{/if}}

<div class="thing-verb-label">{{$verb_lbl}}</div>

<div class="thing-verb">{{$verb_select}}</div>


<label class="thing-label" for="thing-term">{{$thing_lbl}}</label>
<input type="text" class="thing-input" id="thing-term" name="term" />
<div class="thing-field-end"></div>
<label class="thing-label" for="thing-url">{{$url_lbl}}</label>
<input type="text" class="thing-input" id="thing-url" name="url" />
<div class="thing-field-end"></div>
<label class="thing-label" for="thing-img">{{$img_lbl}}</label>
<input type="text" class="thing-input" id="thing-img" name="img" />
<div class="thing-field-end"></div>

<div class="thing-end"></div> 

<input type="submit" class="thing-submit" name="submit" value="{{$submit}}" />
</form>
