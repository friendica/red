function dirdetails(hash) {

	$.get('dirprofile' + '?f=&hash=' + hash, function( data ) {
		$.colorbox({ html: data });
	});

}

