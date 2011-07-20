
<script language="javascript" type="text/javascript">

var editor;
var textlen = 0;


tinyMCE.init({
	theme : "advanced",
	mode : "specific_textareas",
	editor_selector: /(profile-jot-text|prvmail-text)/,
	plugins : "bbcode,paste,autoresize",
	theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,formatselect,code",
	theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "center",
	theme_advanced_blockformats : "blockquote,code",
	paste_text_sticky : true,
	entity_encoding : "raw",
	add_unload_trigger : false,
	remove_linebreaks : false,
	force_p_newlines : false,
	force_br_newlines : true,
	forced_root_block : '',
	convert_urls: false,
	content_css: "$baseurl/view/custom_tinymce.css",
	theme_advanced_path : false,
	setup : function(ed) {
		 //Character count
		ed.onKeyUp.add(function(ed, e) {
			var txt = tinyMCE.activeEditor.getContent();
			textlen = txt.length;
			if(textlen != 0 && $('#jot-perms-icon').is('.unlock')) {
				$('#profile-jot-desc').html(ispublic);
			}
			else {
				$('#profile-jot-desc').html('&nbsp;');
			}	 

			if(textlen <= 140) {
				$('#character-counter').removeClass('red');
				$('#character-counter').removeClass('orange');
				$('#character-counter').addClass('grey');
			}
			if((textlen > 140) && (textlen <= 420)) {
				$('#character-counter').removeClass('grey');
				$('#character-counter').removeClass('red');
				$('#character-counter').addClass('orange');
			}
			if(textlen > 420) {
				$('#character-counter').removeClass('grey');
				$('#character-counter').removeClass('orange');
				$('#character-counter').addClass('red');
			}
			$('#character-counter').text(textlen);
		});

		ed.onInit.add(function(ed) {
			ed.pasteAsPlainText = true;
		});

	}
});


</script>
<script type="text/javascript" src="include/ajaxupload.js" ></script>
<script>
	var ispublic = '$ispublic';
	$(document).ready(function() {
		
		$("#profile-jot-acl-wrapper").hide();
		$("a#jot-perms-icon").fancybox({
			'transitionIn' : 'none',
			'transitionOut' : 'none'
		}); 
	
		var uploader = new window.AjaxUpload(
			'wall-image-upload',
			{ action: 'wall_upload/$nickname',
				name: 'userfile',
				onSubmit: function(file,ext) { $('#profile-rotator').show(); },
				onComplete: function(file,response) {
					tinyMCE.execCommand('mceInsertRawHTML',false,response);
					$('#profile-rotator').hide();
				}				 
			}
		);
		var file_uploader = new window.AjaxUpload(
			'wall-file-upload',
			{ action: 'wall_attach/$nickname',
				name: 'userfile',
				onSubmit: function(file,ext) { $('#profile-rotator').show(); },
				onComplete: function(file,response) {
					tinyMCE.execCommand('mceInsertRawHTML',false,response);
					$('#profile-rotator').hide();
				}				 
			}
		);


	});

	function deleteCheckedItems() {
		var checkedstr = '';

		$('.item-select').each( function() {
			if($(this).is(':checked')) {
				if(checkedstr.length != 0) {
					checkedstr = checkedstr + ',' + $(this).val();
				}
				else {
					checkedstr = $(this).val();
				}
			}	
		});
		$.post('item', { dropitems: checkedstr }, function(data) {
			window.location.reload();
		});
	}

	function jotGetLink() {
		reply = prompt("$linkurl");
		if(reply && reply.length) {
			reply = bin2hex(reply);
			$('#profile-rotator').show();
			$.get('parse_url?url=' + reply, function(data) {
				tinyMCE.execCommand('mceInsertRawHTML',false,data);
				$('#profile-rotator').hide();
			});
		}
	}

	function jotGetVideo() {
		reply = prompt("$utubeurl");
		if(reply && reply.length) {
			tinyMCE.execCommand('mceInsertRawHTML',false,'[youtube]' + reply + '[/youtube]');
		}
	}

	function jotVideoURL() {
		reply = prompt("$vidurl");
		if(reply && reply.length) {
			tinyMCE.execCommand('mceInsertRawHTML',false,'[video]' + reply + '[/video]');
		}
	}

	function jotAudioURL() {
		reply = prompt("$audurl");
		if(reply && reply.length) {
			tinyMCE.execCommand('mceInsertRawHTML',false,'[audio]' + reply + '[/audio]');
		}
	}


	function jotGetLocation() {
		reply = prompt("$whereareu", $('#jot-location').val());
		if(reply && reply.length) {
			$('#jot-location').val(reply);
		}
	}

	function jotTitle() {
		reply = prompt("$title", $('#jot-title').val());
		if(reply && reply.length) {
			$('#jot-title').val(reply);
		}
	}

	function jotShare(id) {
		$('#like-rotator-' + id).show();
		$.get('share/' + id, function(data) {
			tinyMCE.execCommand('mceInsertRawHTML',false,data);
			$('#like-rotator-' + id).hide();
			$(window).scrollTop(0);
		});
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

	function jotClearLocation() {
		$('#jot-coord').val('');
		$('#profile-nolocation-wrapper').hide();
	}

	$geotag

</script>

