function dirdetails(hash) {

	$.get('dirprofile' + '?f=&hash=' + hash, function( data ) {
		$.colorbox({ maxWidth: "50%", maxHeight: "75%", html: data });
	});

}

