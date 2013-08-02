<?php

function _well_known_init(&$a){

	if(argc() > 1) {
		switch(argv(1)) {
			case 'zot-info':
				$a->argc -= 1;
				array_shift($a->argv);
				$a->argv[0] = 'zfinger';
				require_once('mod/zfinger.php');
				zfinger_init($a);
				break;

			case 'webfinger':
				$a->argc -= 1;
				array_shift($a->argv);
				$a->argv[0] = 'wfinger';
				require_once('mod/wfinger.php');
				wfinger_init($a);
				break;

		}
	}

	http_status_exit(404);
}