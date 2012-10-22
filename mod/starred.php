<?php


function starred_init(&$a) {

	$starred = 0;

	if(! local_user())
		killme();
	if($a->argc > 1)
		$message_id = intval($a->argv[1]);
	if(! $message_id)
		killme();

	$r = q("SELECT item_flags FROM item WHERE uid = %d AND id = %d LIMIT 1",
		intval(local_user()),
		intval($message_id)
	);
	if(! count($r))
		killme();

	$item_flags = $r[0]['item_flags'];

	if($item_flags & ITEM_STARRED)
	    $item_flags -= ITEM_STARRED;
	else
		$item_flags = $item_flags | ITEM_STARRED;


	$r = q("UPDATE item SET item_flags = %d WHERE uid = %d and id = %d LIMIT 1",
		intval($item_flags),
		intval(local_user()),
		intval($message_id)
	);
 
	header('Content-type: application/json');
	echo json_encode(array('result' => intval($item_flags & ITEM_STARRED)));
	killme();
}
