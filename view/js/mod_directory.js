function dirdetails(hash) {

	$.get('dirprofile' + '?f=&hash=' + hash, function( data ) {
		$.colorbox({ maxWidth: '75%', maxHeight: '75%', html: data });
		$.colorbox.resize();
	});

}

