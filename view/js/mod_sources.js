$(document).ready(function() { 
	var a; 
	a = $("#id_name").autocomplete({ 
		serviceUrl: baseurl + '/acl',
		minChars: 2,
		width: 250,
		id: 'id-name-ac',
		onSelect: function(value,data) {
			$("#id_xchan").val(data);
		}			
	});

}); 
