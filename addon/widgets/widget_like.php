<?php

function like_widget_name() {
	return "Shows likes";
}
function like_widget_help() {
	return "Search first item which contains <em>KEY</em> and print like/dislike count";
}

function like_widget_args(){
	return Array("KEY");
}

function like_widget_content(&$a, $conf){
	$args = explode(",",$_GET['a']);
	
	if ($args[0]!=""){
		return " #TODO like/dislike count for item with <em>" .$args[0]. "</em> # ";
	} else {
		return " #TODO# ";
	}
}
