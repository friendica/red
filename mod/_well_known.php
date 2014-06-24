<?php

function _well_known_init(&$a){

	if(argc() > 1) {

		$arr = array('server' => $_SERVER, 'request' => $_REQUEST);
		call_hooks('well_known', $arr);

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

			case 'host-meta':
				$a->argc -= 1;
				array_shift($a->argv);
				$a->argv[0] = 'hostxrd';
				require_once('mod/hostxrd.php');
				hostxrd_init($a);
				break;

			default:
				break;

		}
	}

	http_status_exit(404);
}