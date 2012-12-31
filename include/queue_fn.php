<?php

function update_queue_time($id) {
	logger('queue: requeue item ' . $id);
	q("UPDATE outq SET outq_updated = '%s' WHERE outq_hash = '%s' LIMIT 1",
		dbesc(datetime_convert()),
		dbesc($id)
	);
}

function remove_queue_item($id) {
	logger('queue: remove queue item ' . $id);
	q("DELETE FROM outq WHERE hash = '%s' LIMIT 1",
		dbesc($id)
	);
}


