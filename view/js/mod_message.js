$(document).ready(function() { 
	var a; 
	a = $("#recip").autocomplete({ 
		serviceUrl: baseurl + '/acl',
		minChars: 2,
		width: 250,
		id: 'recip-ac',
		onSelect: function(value,data) {
			$("#recip-complete").val(data);
		},
	});

}); 
