<?php

function apply_updates() {

	//check for updated items and remove them
	$x = q("SELECT mid, max(object) AS object FROM item WHERE verb = '%s' AND obj_type = '%s' GROUP BY mid",
		dbesc(ACTIVITY_UPDATE),
		dbesc(ACTIVITY_OBJ_FILE)
	);

	if($x) {

		foreach($x as $xx) {

			$object = json_decode($xx['object'],true);

			$d_mid = $object['d_mid'];
			$u_mid = $xx['mid'];

			$y = q("DELETE FROM item WHERE obj_type = '%s' AND (verb = '%s' AND mid = '%s') OR (verb = '%s' AND mid = '%s')",
				dbesc(ACTIVITY_OBJ_FILE),
				dbesc(ACTIVITY_POST),
				dbesc($d_mid),
				dbesc(ACTIVITY_UPDATE),
				dbesc($u_mid)
			);

		}

	}

}
