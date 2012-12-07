$(document).ready(function() { 
	var a; 
	a = $("#contacts-search").autocomplete({ 
		serviceUrl: baseurl + '/acl',
		minChars: 2,
		width: 350,
	});
	a.setOptions({ autoSubmit: true, params: { type: 'a' }});

}); 

$("#contacts-search").keyup(function(event){
		if(event.keyCode == 13){
			$("#contacts-search-submit").click();
		}
});
$(".autocomplete-w1 .selected").keyup(function(event){
		if(event.keyCode == 13){
			$("#contacts-search-submit").click();
		}
});
