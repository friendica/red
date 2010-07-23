<?php


function wall_upload_post(&$a) {


 $src      = $_FILES['userfile']['tmp_name'];


unlink($src);


	echo "<img src=\"".$a->get_baseurl(). "/images/default-profile.jpg\" alt=\"default\" />";
	killme();

}