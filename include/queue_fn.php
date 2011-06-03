<?php

function update_queue_time($id) {
	logger('queue: requeue item ' . $id);
	q("UPDATE `queue` SET `last` = '%s' WHERE `id` = %d LIMIT 1",
		dbesc(datetime_convert()),
		intval($id)
	);
}

function remove_queue_item($id) {
	logger('queue: remove queue item ' . $id);
	q("DELETE FROM `queue` WHERE `id` = %d LIMIT 1",
		intval($id)
	);
}
