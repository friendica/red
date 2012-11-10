$(document).ready(function() { 
	var a; 
	a = $("#contacts-search").autocomplete({ 
		serviceUrl: baseurl + '/acl',
		minChars: 2,
		width: 350,
	});
	a.setOptions({ params: { type: 'a' }});

}); 
