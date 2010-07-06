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


  function openClose(theID) {
    if(document.getElementById(theID).style.display == "block") { 
      document.getElementById(theID).style.display = "none" 
    }
    else { 
      document.getElementById(theID).style.display = "block" 
    } 
  }
  function openMenu(theID) {
      document.getElementById(theID).style.display = "block" 
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