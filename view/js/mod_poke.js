$(document).ready(function() { 
	var a; 
	a = $("#poke-recip").autocomplete({ 
		serviceUrl: baseurl + '/acl',
		minChars: 2,
		width: 350,
		onSelect: function(value,data) {
			$("#poke-recip-complete").val(data);
		}			
	});
	a.setOptions({ params: { type: 'a' }});

}); 
