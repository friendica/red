<h2>{{$header}}</h2>

<h3>{{$addr}}</h3>

{{if $notself}}
<div id="connection-flag-tabs">
{{$tabs}}
</div>
{{/if}}

{{if $self}}
<div id="autoperm-desc" class="descriptive-paragraph">{{$autolbl}}</div>
{{/if}}


<div id="contact-edit-wrapper">

{{if $notself}}
{{if $slide}}
<h3>{{$lbl_slider}}</h3>

{{$slide}}

{{/if}}
{{/if}}



<h3>{{$permlbl}}</h3>
<div id="perm-desc" class="descriptive-text">{{$permnote}}</div>

<form id="abook-edit-form" action="connections/{{$contact_id}}" method="post" >
<input type="hidden" name="contact_id" value="{{$contact_id}}">
<input id="contact-closeness-mirror" type="hidden" name="closeness" value="{{$close}}" />

{{if $noperms}}
<div id="noperm-msg" class="warning-text">{{$noperms}}</div>
<div id="noperm-text" class="descriptive-text">{{$noperm_desc}}</div>
{{/if}}


{{if $is_pending}}
{{include file="field_checkbox.tpl" field=$unapproved}}
{{/if}}

<br />
<b>{{$quick}}</b>
<ul>
{{if $self}}
<li><span class="fakelink" onclick="connectForum(); $('#abook-edit-form').submit();">{{$forum}}</span></li>
<li><span class="fakelink" onclick="connectSoapBox(); $('#abook-edit-form').submit();">{{$soapbox}}</span></li>
{{/if}}
<li><span class="fakelink" onclick="connectFullShare(); $('#abook-edit-form').submit();">{{$full}}</span></li>
<li><span class="fakelink" onclick="connectCautiousShare(); $('#abook-edit-form').submit();">{{$cautious}}</span></li>
<li><span class="fakelink" onclick="connectFollowOnly(); $('#abook-edit-form').submit();">{{$follow}}</span></li>
</ul>

<div id="abook-advanced" class="fakelink" onclick="openClose('abook-advanced-panel');">{{$advanced}}</div>

<div id="abook-advanced-panel" style="display: none;">

<span class="abook-them">{{$them}}</span><span class="abook-me">{{$me}}</span>
<br />
<br />
{{foreach $perms as $prm}}
{{include file="field_acheckbox.tpl" field=$prm}}
{{/foreach}}
<br />

</div>

{{if $multiprofs }}
<div>
<h3>{{$lbl_vis1}}</h3>
<div>{{$lbl_vis2}}</div>

{{$profile_select}}
</div>
{{/if}}

<input class="contact-edit-submit" type="submit" name="done" value="{{$submit}}" />

</form>
</div>
