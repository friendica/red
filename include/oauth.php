<?php
/** 
 * OAuth server
 * Based on oauth2-php <http://code.google.com/p/oauth2-php/>
 * 
 */

define('TOKEN_DURATION', 300);

require_once("library/OAuth1.php");
require_once("library/oauth2-php/lib/OAuth2.inc");

class FKOAuthDataStore extends OAuthDataStore {
  function gen_token(){
		return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
  }
	
  function lookup_consumer($consumer_key) {
      //echo "<pre>"; var_dump($consumer_key); killme();
	  
		$r = q("SELECT client_id, pw, redirect_uri FROM clients WHERE client_id='%s'",
			dbesc($consumer_key)
		);
		if (count($r))
			return new OAuthConsumer($r[0]['client_id'],$r[0]['pw'],$r[0]['redirect_uri']);
		return null;
  }

  function lookup_token($consumer, $token_type, $token) {
		//echo __file__.":".__line__."<pre>"; var_dump($consumer, $token_type, $token); killme();
		$r = q("SELECT id, secret,scope, expires  FROM tokens WHERE client_id='%s' AND scope='%s' AND id='%s'",
			dbesc($consumer->key),
			dbesc($token_type),
			dbesc($token)
		);
		if (count($r)){
			$ot=new OAuthToken($r[0]['id'],$r[0]['secret']);
			$ot->scope=$r[0]['scope'];
			$ot->expires = $r[0]['expires'];
			return $ot;
		}
		return null;
  }

  function lookup_nonce($consumer, $token, $nonce, $timestamp) {
		//echo __file__.":".__line__."<pre>"; var_dump($consumer,$key); killme();
		$r = q("SELECT id, secret  FROM tokens WHERE client_id='%s' AND id='%s' AND expires=%d",
			dbesc($consumer->key),
			dbesc($nonce),
			intval($timestamp)
		);
		if (count($r))
			return new OAuthToken($r[0]['id'],$r[0]['secret']);
		return null;
  }

  function new_request_token($consumer, $callback = null) {
		$key = $this->gen_token();
		$sec = $this->gen_token();
		$r = q("INSERT INTO tokens (id, secret, client_id, scope, expires) VALUES ('%s','%s','%s','%s', UNIX_TIMESTAMP()+%d)",
				dbesc($key),
				dbesc($sec),
				dbesc($consumer->key),
				'request',
				intval(TOKEN_DURATION));
		if (!$r) return null;
		return new OAuthToken($key,$sec);
  }

  function new_access_token($token, $consumer, $verifier = null) {
    // return a new access token attached to this consumer
    // for the user associated with this token if the request token
    // is authorized
    // should also invalidate the request token
    
    $ret=Null;
    
    if (!is_null($token) && $token->expires > time()){
		
		$key = $this->gen_token();
		$sec = $this->gen_token();
		$r = q("INSERT INTO tokens (id, secret, client_id, scope, expires) VALUES ('%s','%s','%s','%s', UNIX_TIMESTAMP()+%d)",
				dbesc($key),
				dbesc($sec),
				dbesc($consumer->$key),
				'access',
				intval(TOKEN_DURATION));
		if ($r)
			$ret = new OAuthToken($key,$sec);		
	}
		
		
	q("DELETE FROM tokens WHERE id='%s'", $token->key);
		
    return $ret;
    
  }
}

class FKOAuth1 extends OAuthServer {
	function __construct() {
		parent::__construct(new FKOAuthDataStore());
		$this->add_signature_method(new OAuthSignatureMethod_PLAINTEXT());
		$this->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
	}
}

class FKOAuth2 extends OAuth2 {

	private function db_secret($client_secret){
		return hash('whirlpool',$client_secret);
	}

	public function addClient($client_id, $client_secret, $redirect_uri) {
		$client_secret = $this->db_secret($client_secret);
		$r = q("INSERT INTO clients (client_id, pw, redirect_uri) VALUES ('%s', '%s', '%s')",
			dbesc($client_id),
			dbesc($client_secret),
			dbesc($redirect_uri)
		);
		  
		return $r;
	}

	protected function checkClientCredentials($client_id, $client_secret = NULL) {
		$client_secret = $this->db_secret($client_secret);
		
		$r = q("SELECT pw FROM clients WHERE client_id = '%s'",
			dbesc($client_id));

		if ($client_secret === NULL)
			return $result !== FALSE;

		return $result["client_secret"] == $client_secret;
	}

	protected function getRedirectUri($client_id) {
		$r = q("SELECT redirect_uri FROM clients WHERE client_id = '%s'",
				dbesc($client_id));
		if ($r === FALSE)
			return FALSE;

		return isset($r[0]["redirect_uri"]) && $r[0]["redirect_uri"] ? $r[0]["redirect_uri"] : NULL;
	}

	protected function getAccessToken($oauth_token) {
		$r = q("SELECT client_id, expires, scope FROM tokens WHERE id = '%s'",
				dbesc($oauth_token));
	
		if (count($r))
			return $r[0];
		return null;
	}


	
	protected function setAccessToken($oauth_token, $client_id, $expires, $scope = NULL) {
		$r = q("INSERT INTO tokens (id, client_id, expires, scope) VALUES ('%s', '%s', %d, '%s')",
				dbesc($oauth_token),
				dbesc($client_id),
				intval($expires),
				dbesc($scope));
				
		return $r;
	}

	protected function getSupportedGrantTypes() {
		return array(
		  OAUTH2_GRANT_TYPE_AUTH_CODE,
		);
	}


	protected function getAuthCode($code) {
		$r = q("SELECT id, client_id, redirect_uri, expires, scope FROM auth_codes WHERE id = '%s'",
				dbesc($code));
		
		if (count($r))
			return $r[0];
		return null;
	}

	protected function setAuthCode($code, $client_id, $redirect_uri, $expires, $scope = NULL) {
		$r = q("INSERT INTO auth_codes 
					(id, client_id, redirect_uri, expires, scope) VALUES 
					('%s', '%s', '%s', %d, '%s')",
				dbesc($code),
				dbesc($client_id),
				dbesc($redirect_uri),
				intval($expires),
				dbesc($scope));
		return $r;	  
	}	
	
}
