$(document).ready(function() { 
	$("#recip").contact_autocomplete(baseurl + '/acl', '', false, function(data) {
		$("#recip-complete").val(data.xid);
	});

}); 
