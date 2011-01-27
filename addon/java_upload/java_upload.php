<?php






function java_upload_photo_post_init(&$a,&$b) {

	if($_POST['partitionCount'])
		$a->data['java_upload'] = true;
	else
		$a->data['java_upload'] = false;


}


function java_upload_photo_post_end(&$a,&$b) {

	if(x($a->data,'java_upload') && $a->data['java_upload'])
		killme();

}