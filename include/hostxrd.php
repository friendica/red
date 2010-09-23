<?php

	$tpl = load_view_file('view/xrd_host.tpl');
	echo str_replace('$domain',$this->hostname,$tpl);
	session_write_close();
	exit();
