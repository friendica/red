<?php

function login_content(&$a) {
	if(local_channel())
		goaway(z_root());
	return login(($a->config['system']['register_policy'] == REGISTER_CLOSED) ? false : true);
}
