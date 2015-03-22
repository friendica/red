<div class="generic-content-wrapper-styled">
<h3>{{$header}}</h3>

{{if $links}}
{{foreach $links as $l}}
<a class="channels-links" href="{{$l.0}}" title="{{$l.1}}">{{$l.2}}</a>
{{/foreach}}
{{/if}} 
<div class="channels-break"></div>

{{if $channel_usage_message}}
<div id="channel-usage-message" class="usage-message">
{{$channel_usage_message}}
</div>
{{/if}}
<div id="channels-desc" class="descriptive-text">{{$desc}}</div>

<div id="all-channels">
{{foreach $all_channels as $chn}}
{{include file="channel.tpl" channel=$chn}}
{{/foreach}} 
</div>

<div class="channels-end all"></div>

{{if $delegates}}
<hr />
<h3>{{$delegate_header}}</h3>
<div id="delegated-channels">
{{foreach $delegates as $chn}}
{{include file="channel.tpl" channel=$chn}}
{{/foreach}} 
</div>

<div class="channels-end all"></div>
{{/if}}

</div>
