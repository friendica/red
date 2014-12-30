<div id="files-mkdir-tools" class="section-content-tools-wrapper form-group">
	<label for="files-mkdir">{{$folder_header}}</label>
	<form method="post" action="">
		<input type="hidden" name="sabreAction" value="mkcol">
		<input id="files-mkdir" type="text" name="name" class="form-control form-group">
		<button class="btn btn-primary btn-sm pull-right" type="submit" value="{{$folder_submit}}">{{$folder_submit}}</button>
	</form>
	<div class="clear"></div>
</div>
<div id="files-upload-tools" class="section-content-tools-wrapper form-group">
	<label for="files-upload">{{$upload_header}}</label>
	<form method="post" action="" enctype="multipart/form-data">
		<input type="hidden" name="sabreAction" value="put">
		<input class="form-group" id="files-upload" type="file" name="file">
		<button class="btn btn-primary btn-sm pull-right" type="submit" value="{{$upload_submit}}">{{$upload_submit}}</button>
		<!-- Name (optional): <input type="text" name="name"> we should rather provide a rename action in edit form-->
	</form>
	<div class="clear"></div>
</div>
