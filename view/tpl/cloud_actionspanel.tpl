<div id="files-mkdir-tools" class="section-content-tools-wrapper form-group">
	<label for="files-mkdir">{{$folder_header}}</label>
	<form method="post" action="">
		<input type="hidden" name="sabreAction" value="mkcol">
		<input id="files-mkdir" type="text" name="name">
		<input type="submit" value="{{$folder_submit}}">
	</form>
</div>
<div id="files-upload-tools" class="section-content-tools-wrapper form-group">
	<label for="files-upload">{{$upload_header}}</label>
	<form method="post" action="" enctype="multipart/form-data">
		<input type="hidden" name="sabreAction" value="put">
		<input id="files-upload" type="file" name="file" style="display: inline;">
		<input type="submit" value="{{$upload_submit}}">
		<!-- Name (optional): <input type="text" name="name"> we should rather provide a rename action in edit form-->
	</form>
</div>
