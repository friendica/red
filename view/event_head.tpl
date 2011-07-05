<script language="javascript" type="text/javascript"
          src="$baseurl/library/tinymce/jscripts/tiny_mce/tiny_mce_src.js"></script>
          <script language="javascript" type="text/javascript">


tinyMCE.init({
	theme : "advanced",
	mode : "textareas",
	plugins : "bbcode,paste",
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
	content_css: "$baseurl/view/custom_tinymce.css",
	theme_advanced_path : false,
	setup : function(ed) {
		ed.onInit.add(function(ed) {
            ed.pasteAsPlainText = true;
        });
    }

});


$(document).ready(function() { 

	$('#event-share-checkbox').change(function() {

		if ($('#event-share-checkbox').is(':checked')) { 
			$('#acl-wrapper').show();
		}
		else {
			$('#acl-wrapper').hide();
		}
	}).trigger('change');


	$('#contact_allow, #contact_deny, #group_allow, #group_deny').change(function() {
		var selstr;
		$('#contact_allow option:selected, #contact_deny option:selected, #group_allow option:selected, #group_deny option:selected').each( function() {
			selstr = $(this).text();
			$('#jot-public').hide();
		});
		if(selstr == null) {
			$('#jot-public').show();
		}

	}).trigger('change');

});

</script>

