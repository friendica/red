	
	<div class='field richtext'>
		<label for='id_$field.0'>$field.1</label>
		<textarea name='$field.0' id='id_$field.0' class="fieldRichtext">$field.2</textarea>
		<span class='field_help'>$field.3</span>
		<script>
			console.log(typeof tinyMCE);
			if(typeof tinyMCE == "undefined") {
				tinyMCE="loading";
				window.tinyMCEPreInit = {
					suffix:"",
					base: baseurl+"/library/tinymce/jscripts/tiny_mce/",
					query:"",
				};
				$(function(){
					$.getScript(baseurl	+"/library/tinymce/jscripts/tiny_mce/tiny_mce_src.js", function(){
						tinyMCE.init({
							theme : "advanced",
							mode : "specific_textareas",
							editor_selector: "fieldRichtext",
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
							convert_urls: false,
							content_css: baseurl+"/view/custom_tinymce.css",
							theme_advanced_path : false,
						});
					});
				});
			}
		</script>
		
	</div>
