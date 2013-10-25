<h3>{{$prvmsg_header}}</h3>
<div class="generic-content-wrapper">
{{foreach $mails as $mail}}
	{{include file="mail_conv.tpl"}}
{{/foreach}}

{{if $canreply}}
{{include file="prv_message.tpl"}}
{{else}}
{{$unknown_text}}
{{/if}}
</div>
