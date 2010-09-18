
<script language="javascript" type="text/javascript" src="$baseurl/tinymce/jscripts/tiny_mce/tiny_mce_src.js"></script>
<script language="javascript" type="text/javascript">

tinyMCE.init({
	theme : "advanced",
	mode : "specific_textareas",
	editor_selector: /(profile-jot-text|prvmail-text)/,
	plugins : "bbcode",
	theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,formatselect",
	theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "center",
	theme_advanced_blockformats : "blockquote,code",
	entity_encoding : "raw",
	add_unload_trigger : false,
	remove_linebreaks : false,
	convert_urls: false,
	content_css: "$baseurl/view/custom_tinymce.css",
	     //Character count
	theme_advanced_path : false,
	setup : function(ed) {
		ed.onKeyUp.add(function(ed, e) {
			var txt = tinyMCE.activeEditor.getContent();
			var text = txt.length;
			if(txt.length <= 140) {
				$('#character-counter').removeClass('red');
				$('#character-counter').removeClass('orange');
				$('#character-counter').addClass('grey');
			}
			if((txt.length > 140) && (txt .length <= 420)) {
				$('#character-counter').removeClass('grey');
				$('#character-counter').removeClass('red');
				$('#character-counter').addClass('orange');
			}
			if(txt.length > 420) {
				$('#character-counter').removeClass('grey');
				$('#character-counter').removeClass('orange');
				$('#character-counter').addClass('red');
			}
			$('#character-counter').text(text);
    		});
     	}
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
		$('#contact_allow, #contact_deny, #group_allow, #group_deny').change(function() {
			var selstr;
			$('#contact_allow option:selected, #contact_deny option:selected, #group_allow option:selected, #group_deny option:selected').each( function() {
				selstr = $(this).text();
				$('#profile-jot-perms img').attr('src', 'images/lock_icon.gif');

			});
			if(selstr == null)
				$('#profile-jot-perms img').attr('src', 'images/unlock_icon.gif');

		}).trigger('change');

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

	function jotGetVideo() {
		reply = prompt("Please enter a YouTube link:");
		if(reply && reply.length) {
			tinyMCE.execCommand('mceInsertRawHTML',false,'[youtube]' + reply + '[/youtube]');
		}
	}

	function jotGetLocation() {
		reply = prompt("Where are you right now?", $('#jot-location').val());
		if(reply && reply.length) {
			$('#jot-location').val(reply);
		}
	}


	function linkdropper(event) {
		var linkFound = event.dataTransfer.types.contains("text/uri-list");
		if(linkFound)
			event.preventDefault();
	}

	function linkdrop(event) {
		var reply = event.dataTransfer.getData("text/uri-list");
		event.target.textContent = reply;
		event.preventDefault();
		if(reply && reply.length) {
			$('#profile-rotator').show();
			$.get('parse_url?url=' + reply, function(data) {
				tinyMCE.execCommand('mceInsertRawHTML',false,data);
				$('#profile-rotator').hide();
			});
		}
	}


</script>

