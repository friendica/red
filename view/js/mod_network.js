$(document).ready(function() { 
	var a; 
	a = $("#search-text").autocomplete({ 
		serviceUrl: baseurl + '/search_ac',
		minChars: 2,
		width: 250,
	});
}); 

