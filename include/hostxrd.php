<?php

	$tpl = file_get_contents('view/xrd_host.tpl');
	echo str_replace('$domain',$this->hostname,$tpl);
	session_write_close();
	exit();
