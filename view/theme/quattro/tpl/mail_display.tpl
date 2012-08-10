<div id="mail-display-subject">
	<span class="{{if $thread_seen}}seen{{else}}unseen{{endif}}">$thread_subject</span>
	<a href="message/dropconv/$thread_id" onclick="return confirmDelete();"  title="$delete" class="mail-delete icon s22 delete"></a>
</div>

{{ for $mails as $mail }}
	<div id="thread-wrapper-$mail.id" class="thread-wrapper">
		{{ inc mail_conv.tpl }}{{endinc}}
	</div>
{{ endfor }}

{{ inc prv_message.tpl }}{{ endinc }}
