$(document).ready(function() { 
	$("#recip").contact_autocomplete(baseurl + '/acl', function(data) {
		$("#recip-complete").val(data.xid);
	});

}); 
