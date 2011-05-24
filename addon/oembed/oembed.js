function oembed(){
	var reply = prompt("$oembed_message:");
	if(reply && reply.length) { 
		  tinyMCE.execCommand('mceInsertRawHTML',false, "[embed]"+reply+"[/embed]" );
	}
}
