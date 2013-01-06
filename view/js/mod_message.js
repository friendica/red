$(document).ready(function() { 
	var a; 
	a = $("#recip").autocomplete({ 
		serviceUrl: baseurl + '/acl',
		minChars: 2,
		width: 350,
		onSelect: function(value,data) {
			$("#recip-complete").val(data);
		}			
	});

}); 
