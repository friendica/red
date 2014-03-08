
$(document).ready(function() {
	if(typeof(after_following) !== 'undefined' && after_following)
		connectFullShare();

	$('#id_pending').click(function() {
		connectFullShare();
	});
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

	$('.abook-permschange').show();
	$('.abook-permschange').html(aStr['permschange']);
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
	$('.abook-permschange').show();
	$('.abook-permschange').html(aStr['permschange']);

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
	$('.abook-permschange').show();
	$('.abook-permschange').html(aStr['permschange']);

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
	$('.abook-permschange').show();
	$('.abook-permschange').html(aStr['permschange']);
}


function connectFollowOnly() {
	$('.abook-edit-me').each(function() {
		if(! $(this).is(':disabled'))
			$(this).removeAttr('checked');
	});

	$('#me_id_perms_send_stream').attr('checked','checked');
	$('.abook-permschange').show();
	$('.abook-permschange').html(aStr['permschange']);

}

