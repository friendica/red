<?php




function contact_remove($id) {
	q("DELETE FROM `contact` WHERE `id` = %d LIMIT 1",
		intval($id)
	);
	q("DELETE FROM `item` WHERE `contact-id` = %d ",
		intval($id)
	);
	q("DELETE FROM `photo` WHERE `contact-id` = %d ",
		intval($id)
	);
}


// Contact has refused to recognise us as a friend. We will start a countdown.
// If they still don't recognise us in 32 days, the relationship is over,
// and we won't waste any more time trying to communicate with them.
// This provides for the possibility that their database is temporarily messed
// up or some other transient event and that there's a possibility we could recover from it.
 
if(! function_exists('mark_for_death')) {
function mark_for_death($contact) {
	if($contact['term-date'] == '0000-00-00 00:00:00') {
		q("UPDATE `contact` SET `term-date` = '%s' WHERE `id` = %d LIMIT 1",
				dbesc(datetime_convert()),
				intval($contact['id'])
		);
	}
	else {
		$expiry = $contact['term-date'] . ' + 32 days ';
		if(datetime_convert() > datetime_convert('UTC','UTC',$expiry)) {

			// relationship is really truly dead. 

			contact_remove($contact['id']);

		}
	}

}}

if(! function_exists('unmark_for_death')) {
function unmark_for_death($contact) {
	// It's a miracle. Our dead contact has inexplicably come back to life.
	q("UPDATE `contact` SET `term-date = '%s' WHERE `id` = %d LIMIT 1",
		dbesc('0000-00-00 00:00:00'),
		intval($contact['id'])
	);
}}

