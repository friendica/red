<?php

function login_content(&$a) {
	return login(($a->config['register_policy'] == REGISTER_CLOSED) ? false : true);
}