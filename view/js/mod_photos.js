
var ispublic = aStr['everybody'];

$(document).ready(function() {

	$("a#photos-upload-perms-menu").colorbox({
		'inline' : true, 
		'transition' : 'elastic' 
	});

	$("a#settings-default-perms-menu").colorbox({ 
		'inline' : true, 
		'transition' : 'elastic' 
	});

	var a;
	a = $("#photo-edit-newtag").autocomplete({ 
		serviceUrl: baseurl + '/acl',
		minChars: 2,
		width: 250,
		id: 'newtag-ac',
		onSelect: function(value,data) {
			$("#photo-edit-newtag").val(data);
		},
	});
	a.setOptions({ params: { type: 'p' }});


	$('#contact_allow, #contact_deny, #group_allow, #group_deny').change(function() {
		var selstr;
		$('#contact_allow option:selected, #contact_deny option:selected, #group_allow option:selected, #group_deny option:selected').each( function() {
			selstr = $(this).text();
			$('#jot-perms-icon').removeClass('icon-unlock').addClass('icon-lock');
			$('#jot-public').hide();
		});
		if(selstr == null) { 
			$('#jot-perms-icon').removeClass('icon-lock').addClass('icon-unlock');
			$('#jot-public').show();
		}

	}).trigger('change');
});
