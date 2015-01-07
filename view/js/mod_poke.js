$(document).ready(function() { 
	$("#poke-recip").contact_autocomplete(baseurl + '/acl', 'a', function(data) {
		$("#poke-recip-complete").val(data.id);
	});
}); 
