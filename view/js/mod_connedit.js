
function abook_perms_msg() {
//	$('.abook-permsmsg').show();
//	$('.abook-permschange').html(aStr['permschange']);
//	$('.abook-permssave').show();
}

function abook_perms_new() {
//	$('.abook-permsnew').show();
//	$('.abook-permssave').show();
}


$(document).ready(function() {
	if(typeof(after_following) !== 'undefined' && after_following) {
		if(typeof(connectDefaultShare) !== 'undefined')
			connectDefaultShare();
		else
			connectFullShare();
		abook_perms_new();
	}

	$('#id_pending').click(function() {
		if(typeof(connectDefaultShare) !== 'undefined')
			connectDefaultShare();
		else
			connectFullShare();
		abook_perms_new();
	});

//	$('.abook-edit-me').click(function() {
//		abook_perms_msg();
//	});

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
	$('#me_id_perms_post_like').attr('checked','checked');
//	abook_perms_msg();
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
	$('#me_id_perms_post_like').attr('checked','checked');
//	abook_perms_msg();

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
	$('#me_id_perms_post_like').attr('checked','checked');
//	abook_perms_msg();

}

function connectClear() {
	$('.abook-edit-me').each(function() {
		if(! $(this).is(':disabled'))
			$(this).removeAttr('checked');
	});
//	abook_perms_msg();

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
//	abook_perms_msg();

}


function connectFollowOnly() {
	$('.abook-edit-me').each(function() {
		if(! $(this).is(':disabled'))
			$(this).removeAttr('checked');
	});

	$('#me_id_perms_send_stream').attr('checked','checked');
//	abook_perms_msg();

}

