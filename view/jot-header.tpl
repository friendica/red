<script language="javascript" type="text/javascript"
          src="$baseurl/tinymce/jscripts/tiny_mce/tiny_mce_src.js"></script>
          <script language="javascript" type="text/javascript">

tinyMCE.init({
	theme : "advanced",
	mode : "specific_textareas",
	editor_selector: "profile-jot-text",
	plugins : "bbcode",
	theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor",
	theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "center",
	theme_advanced_styles : "Code=codeStyle;Quote=quoteStyle",
	content_css : "bbcode.css",
	entity_encoding : "raw",
	add_unload_trigger : false,
	remove_linebreaks : false,
	content_css: "$baseurl/view/custom_tinymce.css"
});

</script>
<script type="text/javascript" src="include/ajaxupload.js" ></script>
<script>
	$(document).ready(function() {
		var uploader = new window.AjaxUpload(
			'wall-image-upload',
			{ action: 'wall_upload',
				name: 'userfile',
				onSubmit: function(file,ext) { $('#profile-rotator').show(); },
				onComplete: function(file,response) {
					tinyMCE.execCommand('mceInsertRawHTML',false,response);
					$('#profile-rotator').hide();
				}				 
			}
		);

	});


	function jotGetLink() {
		reply = prompt("Please enter a link URL:");
		if(reply && reply.length) {
			$('#profile-rotator').show();
			$.get('parse_url?url=' + reply, function(data) {
				tinyMCE.execCommand('mceInsertRawHTML',false,data);
				$('#profile-rotator').hide();
			});
		}
	}

	var src = null;

	$(document).ready(function() {
		if($('#live-network').length) { src = 'network';  setTimeout(liveUpdate, 30000); }
		if($('#live-profile').length) { src = 'profile';  setTimeout(liveUpdate, 30000); }
	});

	function liveUpdate() {
		if(src == null) { return; }
		if($('.comment-edit-text-full').length) {
			setTimeout(liveUpdate, 30000);
			return;
		}

//		$.get('update_' + src,function(data)
//			{
//			$(data).find('#wall-item-outside-wrapper').each(function() {
//				var net = $(this).find('net').text();
//				if(net == 0) { net = ''; }
//				$('#net-update').html(net);
//				var home = $(this).find('home').text();
//				if(home == 0) { home = ''; }
//				$('#home-update').html(home);
//				var mail = $(this).find('mail').text();
//				if(mail == 0) { mail = ''; }
//				$('#mail-update').html(mail);
//				var intro = $(this).find('intro').text();
//				if(intro == 0) { intro = ''; }
//				$('#notify-update').html(intro);
//			});
//		}) ;

		setTimeout(liveUpdate,30000);
	}
			



</script>

<!--

	relative_urls: false,
        document_base_url : "$baseurl/",
         external_image_list_url : "$baseurl/include/imagelist-js.php",
         content_css : "$baseurl/view/tiny.css"

});
</script>
-->