{{foreach $mails as $mail}}
	{{include file="mail_conv.tpl"}}
{{/foreach}}

{{if $canreply}}
{{include file="prv_message.tpl"}}
{{else}}
{{$unknown_text}}
{{/if}}
