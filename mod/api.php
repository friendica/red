<?php

require_once('include/api.php');

function oauth_get_client(){
	// get consumer/client from request token
	try {
		$request = OAuthRequest::from_request();
	} catch(Exception $e) {
		echo "<pre>"; var_dump($e); killme();
	}
	
	$params = $request->get_parameters();
	$token = $params['oauth_token'];
	
	$r = q("SELECT `clients`.* 
			FROM `clients`, `tokens` 
			WHERE `clients`.`client_id`=`tokens`.`client_id` 
			AND `tokens`.`id`='%s' AND `tokens`.`scope`='request'",
			dbesc($token));

	if (!count($r))
		return null;
	
	return $r[0];
}

function api_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if(count($a->user) && x($a->user,'uid') && $a->user['uid'] != local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

}

function api_content(&$a) {
	if ($a->cmd=='api/oauth/authorize'){
		/* 
		 * api/oauth/authorize interact with the user. return a standard page
		 */
		
		
		if (x($_POST,'oauth_yes')){
		
		
			$app = oauth_get_client();
			if (is_null($app)) return "Invalid request. Unknown token.";
			$consumer = new OAuthConsumer($app['key'], $app['secret']);
			
			// Rev A change
			$request = OAuthRequest::from_request();
			$callback = $request->get_parameter('oauth_callback');
			$datastore = new FKOAuthDataStore();
			$new_token = $datastore->new_request_token($consumer, $callback);
			
			$tpl = get_markup_template("oauth_authorize_done.tpl");
			$o = replace_macros($tpl, array(
				'$title' => t('Authorize application connection'),
				'$info' => t('Return to your app and insert this Securty Code:'),
				'$code' => $new_token->key,
			));
		
			return $o;
		
		
		}
	
		
		
		if(! local_user()) {
			//TODO: we need login form to redirect to this page
			notice( t('Please login to continue.') . EOL );
			return login(false);
		}
		
		$app = oauth_get_client();
		if (is_null($app)) return "Invalid request. Unknown token.";
		
		
		$tpl = get_markup_template('oauth_authorize.tpl');
		$o = replace_macros($tpl, array(
			'$title' => t('Authorize application connection'),
			'$app' => $app,
			'$authorize' => t('Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'),
			'$yes'	=> t('Yes'),
			'$no'	=> t('No'),
		));
		
		//echo "<pre>"; var_dump($app); killme();
		
		return $o;
	}
	
	echo api_call($a);
	killme();
}



