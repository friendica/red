<div class="group-delete-wrapper button" id="group-delete-wrapper-{{$id}}" >
	<a href="group/drop/{{$id}}?t={{$form_security_token}}" 
		onclick="return confirmDelete();" 
		id="group-delete-icon-{{$id}}" 
		class="group-delete-icon" 
		onmouseover="imgbright(this);" 
		onmouseout="imgdull(this);" ><i class="icon-remove drop-icons"></i></a>
</div>
<div class="group-delete-end"></div>
