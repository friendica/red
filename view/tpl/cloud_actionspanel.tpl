<table>
	<tr>
		<td><strong>{{$folder_header}}</strong>&nbsp;&nbsp;&nbsp;</td>
		<td>
			<form method="post" action="">
				<input type="hidden" name="sabreAction" value="mkcol">
				<input type="text" name="name">
				<input type="submit" value="{{$folder_submit}}">
			</form>
		</td>
	</tr>
	<tr>
		<td><strong>{{$upload_header}}</strong>&nbsp;&nbsp;&nbsp;</td>
		<td>
			<form method="post" action="" enctype="multipart/form-data">
				<input type="hidden" name="sabreAction" value="put">
				<input type="file" name="file" style="display: inline;">
				<input type="submit" value="{{$upload_submit}}">
				<!-- Name (optional): <input type="text" name="name"> we should rather provide a rename action in edit form-->
			</form>
		</td>
	</tr>
</table>