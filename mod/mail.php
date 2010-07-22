<?php


function mail_post(&$a) {

	// handles mail created by me, or mail posted to my profile page.
	// If remote we must have a DFRN-url.

	if((x($_POST,'dfrn_url')) && (strlen($_POST['dfrn_url']))) {
		// get post params
		$remote = true;
		
		// check blacklist


		// scrape url



		// sanitise

		// store


		// notify


	}

	if(local_user()) {


		// get data


		// sanitise


		// store


		// notify




	}
	








}


function mail_content(&$a) {

	// remot mail


	// list mail





	// read message




	// reply




	// new mail



}