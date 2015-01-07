$(document).ready(function() { 
	$("#contacts-search").contact_autocomplete(baseurl + '/acl', 'a', true);
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

