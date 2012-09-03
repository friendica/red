<?php

function _well_known_init(&$a){

	if(argc() > 1) {
		switch(argv(1)) {
			case "host-meta":
				require_once('mod/hostxrd.php');
				hostxrd_init($a);
				break;

			case 'zot-guid':
				$a->argc -= 1;
				array_shift($a->argv);
				$a->argv[0] = 'zfinger';
				require_once('mod/zfinger.php');
				zfinger_init($a);
				break;

		}
	}

	http_status_exit(404);
}