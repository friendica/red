<!DOCTYPE html>
<html>
	<head>
	<script type="text/javascript" src="$baseurl/library/tinymce/jscripts/tiny_mce/tiny_mce_popup.js"></script>
	<style>
		.filebrowser.path { font-family: fixed; font-size: 10px;}
		.filebrowser ul{ list-style-type: none; padding:0px; }
		.filebrowser.files img { height:50px;}
		.filebrowser.files.image li { display: block; padding: 5px; float: left; }
		.filebrowser.files.image span { display: none;}
		.filebrowser.files.file img { height:16px;}
		.filebrowser a { text-decoration: none; }
	</style>
	<script>
		var FileBrowserDialogue = {
			init : function () {
				// Here goes your code for setting your custom things onLoad.
			},
			mySubmit : function (URL) {
				//var URL = document.my_form.my_field.value;
				var win = tinyMCEPopup.getWindowArg("window");

				// insert information now
				win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = URL;

				// are we an image browser
				if (typeof(win.ImageDialog) != "undefined") {
					// we are, so update image dimensions...
					if (win.ImageDialog.getImageData)
						win.ImageDialog.getImageData();

					// ... and preview if necessary
					if (win.ImageDialog.showPreviewImage)
						win.ImageDialog.showPreviewImage(URL);
				}

				// close popup window
				tinyMCEPopup.close();
			}
		}

		tinyMCEPopup.onInit.add(FileBrowserDialogue.init, FileBrowserDialogue);
	</script>
	</head>
	<body>
	<div class="filebrowser path">
		&gt; {{ for $path as $p }}<a href="$p.0">$p.1</a> / {{ endfor }}
	</div>
	<div class="filebrowser folders">
		<ul>
			{{ for $folders as $f }}<li><a href="$f.0/">$f.1</a></li>{{ endfor }}
		</ul>
	</div>
	<form>
		<div class="filebrowser files $type">
			<ul>
			{{ for $files as $f }}
				<li><a href="#" onclick="FileBrowserDialogue.mySubmit('$f.0'); return false;"><img src="$f.2"><span>$f.1</span></a></li>
			{{ endfor }}
			</ul>
		</div>
	</form>
	</body>
	
</html>
