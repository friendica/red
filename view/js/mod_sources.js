$(document).ready(function() { 
	var a; 
	a = $("#id_name").autocomplete({ 
		serviceUrl: baseurl + '/acl',
		minChars: 2,
		width: 350,
		onSelect: function(value,data) {
			$("#id_xchan").val(data);
		}			
	});

}); 
