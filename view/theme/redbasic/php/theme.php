<?php

function redbasic_init(&$a) {
	if($a->config['system']['theme_engine'])
		$a->set_template_engine($a->config['system']['theme_engine']);
	else
		$a->set_template_engine('smarty3');
//	head_add_js('redbasic.js');
}
