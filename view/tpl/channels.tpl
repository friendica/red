<h3>{{$header}}</h3>


{{if $links}}
{{foreach $links as $l}}
<a class="channels-links" href="{{$l.0}}" title="{{$l.1}}">{{$l.2}}</a>
{{/foreach}}
{{/if}} 

{{if $selected}}
<div id="selected-channel">
<div id="channels-selected">{{$msg_selected}}</div>
{{include file="channel.tpl" channel=$selected}}
<div class="channels-end selected"></div>
</div>
{{/if}}

<div id="channels-desc" class="descriptive-text">{{$desc}}</div>

<div id="all-channels">
{{foreach $all_channels as $chn}}
{{include file="channel.tpl" channel=$chn}}
{{/foreach}} 
</div>

<div class="channels-end all"></div>
