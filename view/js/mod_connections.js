$(document).ready(function() { 
	var a; 
	a = $("#contacts-search").autocomplete({ 
		serviceUrl: baseurl + '/acl',
		minChars: 2,
		width: 250,
		id: 'contact-search-ac',
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


function connectFullShare() {
	$('.abook-edit-me').each(function() {
		if(! $(this).is(':disabled'))
			$(this).removeAttr('checked');
	});
	$('#me_id_perms_view_stream').attr('checked','checked');
	$('#me_id_perms_view_profile').attr('checked','checked');
	$('#me_id_perms_view_photos').attr('checked','checked');
	$('#me_id_perms_view_contacts').attr('checked','checked');
	$('#me_id_perms_view_storage').attr('checked','checked');
	$('#me_id_perms_view_pages').attr('checked','checked');
	$('#me_id_perms_send_stream').attr('checked','checked');
	$('#me_id_perms_post_wall').attr('checked','checked');
	$('#me_id_perms_post_comments').attr('checked','checked');
	$('#me_id_perms_post_mail').attr('checked','checked');
	$('#me_id_perms_chat').attr('checked','checked');
	$('#me_id_perms_view_storage').attr('checked','checked');
	$('#me_id_perms_republish').attr('checked','checked');
}

function connectCautiousShare() {
	$('.abook-edit-me').each(function() {
		if(! $(this).is(':disabled'))
			$(this).removeAttr('checked');
	});

	$('#me_id_perms_view_stream').attr('checked','checked');
	$('#me_id_perms_view_profile').attr('checked','checked');
	$('#me_id_perms_view_photos').attr('checked','checked');
	$('#me_id_perms_view_storage').attr('checked','checked');
	$('#me_id_perms_view_pages').attr('checked','checked');
	$('#me_id_perms_send_stream').attr('checked','checked');
	$('#me_id_perms_post_comments').attr('checked','checked');
	$('#me_id_perms_post_mail').attr('checked','checked');
}

function connectForum() {
	$('.abook-edit-me').each(function() {
		if(! $(this).is(':disabled'))
			$(this).removeAttr('checked');
	});

	$('#me_id_perms_view_stream').attr('checked','checked');
	$('#me_id_perms_view_profile').attr('checked','checked');
	$('#me_id_perms_view_photos').attr('checked','checked');
	$('#me_id_perms_view_contacts').attr('checked','checked');
	$('#me_id_perms_view_storage').attr('checked','checked');
	$('#me_id_perms_view_pages').attr('checked','checked');
	$('#me_id_perms_send_stream').attr('checked','checked');
	$('#me_id_perms_post_wall').attr('checked','checked');
	$('#me_id_perms_post_comments').attr('checked','checked');
	$('#me_id_perms_post_mail').attr('checked','checked');
	$('#me_id_perms_tag_deliver').attr('checked','checked');
	$('#me_id_perms_republish').attr('checked','checked');

}

function connectSoapBox() {
	$('.abook-edit-me').each(function() {
		if(! $(this).is(':disabled'))
			$(this).removeAttr('checked');
	});

	$('#me_id_perms_view_stream').attr('checked','checked');
	$('#me_id_perms_view_profile').attr('checked','checked');
	$('#me_id_perms_view_photos').attr('checked','checked');
	$('#me_id_perms_view_contacts').attr('checked','checked');
	$('#me_id_perms_view_storage').attr('checked','checked');
	$('#me_id_perms_view_pages').attr('checked','checked');
}


function connectFollowOnly() {
	$('.abook-edit-me').each(function() {
		if(! $(this).is(':disabled'))
			$(this).removeAttr('checked');
	});

	$('#me_id_perms_send_stream').attr('checked','checked');
}

