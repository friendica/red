<div class="generic-content-wrapper-styled">
<h2>{{$header}}</h2>

<h3>{{$addr}}</h3>

{{if $notself}}
<div id="connection-flag-tabs">
{{$tabs}}
</div>
<div id="connection-edit-buttons">
{{foreach $buttons as $b }}
<button class="btn btn-sm btn-default" title="{{$b.title}}" onclick="window.location.href='{{$b.url}}'; return false;">{{$b.label}}</button>
{{/foreach}}
{{/if}}


<div id="contact-edit-wrapper">
<form id="abook-edit-form" action="connedit/{{$contact_id}}" method="post" >

<div class="abook-permsnew" style="display: none;">
<div class="abook-perms-msg">{{$perms_step1}}</div>
</div>

<div class="abook-permsmsg" style="display: none;">
<div class="abook-perms-msg">{{$perms_new}}</div>
</div>


<div class="abook-permssave" style="display: none;">
<input class="contact-edit-submit" type="submit" name="done" value="{{$submit}}" />
</div>

{{if $last_update}}
{{$lastupdtext}} {{$last_update}}
{{/if}}


{{if $is_pending}}
<div class="abook-pending-contact">
{{include file="field_checkbox.tpl" field=$unapproved}}
</div>
{{/if}}


{{if $notself}}
{{if $slide}}
<h3>{{$lbl_slider}}</h3>

{{$slide}}

{{/if}}

{{if $rating}}
<h3>{{$lbl_rating}}</h3>

{{$rating}}


{{/if}}

{{/if}}


{{if $self}}
<div class="abook-autotext">
<div id="autoperm-desc" class="descriptive-paragraph">{{$autolbl}}</div>
{{include file="field_checkbox.tpl" field=$autoperms}}
</div>
{{/if}}

<input type="hidden" name="contact_id" value="{{$contact_id}}">
<input id="contact-closeness-mirror" type="hidden" name="closeness" value="{{$close}}" />
<input id="contact-rating-mirror" type="hidden" name="rating" value="{{$rating_val}}" />


{{if $rating}}
{{if $notself}}
<h3 class="abook-rating-text-desc">{{$lbl_rating_txt}}</h3>
<textarea name="rating_text" id="rating-text" >{{$rating_txt}}</textarea>
{{/if}}
{{/if}}

{{if $notself}}
{{if $multiprofs }}
<div>
<h3>{{$lbl_vis1}}</h3>
<div>{{$lbl_vis2}}</div>

{{$profile_select}}
</div>
{{/if}}
{{/if}}

<h3>{{$permlbl}}</h3>

{{if $notself}}
<div id="connedit-perms-wrap" class="fakelink" onclick="openClose('connedit-perms');">{{$clickme}}</div>
<div id="connedit-perms" style="display: none;" >
{{/if}}
 
<div id="perm-desc" class="descriptive-text">{{$permnote}}</div>
<table>
<tr><td></td><td class="abook-them">{{$them}}</td><td colspan="2" class="abook-me">{{$me}}</td><td></td></tr>
<tr><td colspan="5"><hr /></td></tr>
{{foreach $perms as $prm}}
{{include file="field_acheckbox.tpl" field=$prm}}
{{/foreach}}
<tr><td colspan="5"><hr /></td></tr>
</table>

</div>

{{if $notself}}
</div>
{{/if}}

<input class="contact-edit-submit" type="submit" name="done" value="{{$submit}}" />

{{if $self && $noperms}}
<script>		
	if(typeof(connectDefaultShare) !== 'undefined')
		connectDefaultShare();
	else
		connectFullShare();
	abook_perms_msg();
</script>
{{/if}}

</form>
</div>
</div>
