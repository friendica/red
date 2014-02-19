
var ispublic = aStr['everybody'] ;

$(document).ready(function() {

			  $("a#settings-default-perms-menu").colorbox({ 
				  'inline' : true, 
				  'transition' : 'elastic' 
			});

	$('#contact_allow, #contact_deny, #group_allow, #group_deny').change(function() {
		var selstr;
		$('#contact_allow option:selected, #contact_deny option:selected, #group_allow option:selected, #group_deny option:selected').each( function() {
			selstr = $(this).text();
			$('#jot-perms-icon').removeClass('unlock').addClass('lock');
			$('#jot-public').hide();
		});
		if(selstr == null) { 
			$('#jot-perms-icon').removeClass('lock').addClass('unlock');
			$('#jot-public').show();
		}

	}).trigger('change');

});

function channel_privacy_macro(n) {
    if(n == 0) {
        $('#id_view_stream option').eq(0).attr('selected','selected');
        $('#id_view_profile option').eq(0).attr('selected','selected');
        $('#id_view_photos option').eq(0).attr('selected','selected');
        $('#id_view_contacts option').eq(0).attr('selected','selected');
        $('#id_view_storage option').eq(0).attr('selected','selected');
        $('#id_view_pages option').eq(0).attr('selected','selected');
        $('#id_send_stream option').eq(0).attr('selected','selected');
        $('#id_post_wall option').eq(0).attr('selected','selected');
        $('#id_post_comments option').eq(0).attr('selected','selected');
        $('#id_post_mail option').eq(0).attr('selected','selected');
        $('#id_post_photos option').eq(0).attr('selected','selected');
        $('#id_tag_deliver option').eq(0).attr('selected','selected');
        $('#id_chat option').eq(0).attr('selected','selected');
        $('#id_write_storage option').eq(0).attr('selected','selected');
        $('#id_write_pages option').eq(0).attr('selected','selected');
        $('#id_delegate option').eq(0).attr('selected','selected');
		$('#id_republish option').eq(0).attr('selected','selected');
		$('#id_bookmark option').eq(0).attr('selected','selected');
		$('#id_profile_in_directory_onoff .off').removeClass('hidden');
		$('#id_profile_in_directory_onoff .on').addClass('hidden');
		$('#id_profile_in_directory').val(0);
	}
    if(n == 1) {
        $('#id_view_stream option').eq(1).attr('selected','selected');
        $('#id_view_profile option').eq(1).attr('selected','selected');
        $('#id_view_photos option').eq(1).attr('selected','selected');
        $('#id_view_contacts option').eq(1).attr('selected','selected');
        $('#id_view_storage option').eq(1).attr('selected','selected');
        $('#id_view_pages option').eq(1).attr('selected','selected');
        $('#id_send_stream option').eq(1).attr('selected','selected');
        $('#id_post_wall option').eq(1).attr('selected','selected');
        $('#id_post_comments option').eq(1).attr('selected','selected');
        $('#id_post_mail option').eq(1).attr('selected','selected');
        $('#id_post_photos option').eq(1).attr('selected','selected');
        $('#id_tag_deliver option').eq(1).attr('selected','selected');
        $('#id_chat option').eq(1).attr('selected','selected');
        $('#id_write_storage option').eq(1).attr('selected','selected');
        $('#id_write_pages option').eq(1).attr('selected','selected');
        $('#id_delegate option').eq(0).attr('selected','selected');
		$('#id_republish option').eq(0).attr('selected','selected');
		$('#id_bookmark option').eq(1).attr('selected','selected');
		$('#id_profile_in_directory_onoff .off').removeClass('hidden');
		$('#id_profile_in_directory_onoff .on').addClass('hidden');
		$('#id_profile_in_directory').val(0);
	}
    if(n == 2) {
        $('#id_view_stream option').eq(6).attr('selected','selected');
        $('#id_view_profile option').eq(6).attr('selected','selected');
        $('#id_view_photos option').eq(6).attr('selected','selected');
        $('#id_view_contacts option').eq(6).attr('selected','selected');
        $('#id_view_storage option').eq(6).attr('selected','selected');
        $('#id_view_pages option').eq(6).attr('selected','selected');
        $('#id_send_stream option').eq(2).attr('selected','selected');
        $('#id_post_wall option').eq(1).attr('selected','selected');
        $('#id_post_comments option').eq(2).attr('selected','selected');
        $('#id_post_mail option').eq(1).attr('selected','selected');
        $('#id_post_photos option').eq(0).attr('selected','selected');
        $('#id_tag_deliver option').eq(1).attr('selected','selected');
        $('#id_chat option').eq(1).attr('selected','selected');
        $('#id_write_storage option').eq(0).attr('selected','selected');
        $('#id_write_pages option').eq(0).attr('selected','selected');
        $('#id_delegate option').eq(0).attr('selected','selected');
		$('#id_republish option').eq(1).attr('selected','selected');
		$('#id_bookmark option').eq(1).attr('selected','selected');
		$('#id_profile_in_directory_onoff .on').removeClass('hidden');
		$('#id_profile_in_directory_onoff .off').addClass('hidden');
		$('#id_profile_in_directory').val(1);
	}
    if(n == 3) {
        $('#id_view_stream option').eq(6).attr('selected','selected');
        $('#id_view_profile option').eq(6).attr('selected','selected');
        $('#id_view_photos option').eq(6).attr('selected','selected');
        $('#id_view_contacts option').eq(6).attr('selected','selected');
        $('#id_view_storage option').eq(6).attr('selected','selected');
        $('#id_view_pages option').eq(6).attr('selected','selected');
        $('#id_send_stream option').eq(4).attr('selected','selected');
        $('#id_post_wall option').eq(4).attr('selected','selected');
        $('#id_post_comments option').eq(4).attr('selected','selected');
        $('#id_post_mail option').eq(4).attr('selected','selected');
        $('#id_post_photos option').eq(2).attr('selected','selected');
        $('#id_tag_deliver option').eq(1).attr('selected','selected');
        $('#id_chat option').eq(4).attr('selected','selected');
        $('#id_write_storage option').eq(2).attr('selected','selected');
        $('#id_write_pages option').eq(2).attr('selected','selected');
        $('#id_delegate option').eq(0).attr('selected','selected');
		$('#id_republish option').eq(4).attr('selected','selected');
		$('#id_bookmark option').eq(4).attr('selected','selected');
		$('#id_profile_in_directory_onoff .on').removeClass('hidden');
		$('#id_profile_in_directory_onoff .off').addClass('hidden');
		$('#id_profile_in_directory').val(1);

	}



}
