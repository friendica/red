<?php /** @file */

/**
 * Zot endpoint
 */


require_once('include/zot.php');


function post_init(&$a) {

	// Most access to this endpoint is via the post method.
	// Here we will pick out the magic auth params which arrive
	// as a get request, and the only communications to arrive this way.

/**
 * Magic Auth
 * ==========
 *
 * So-called "magic auth" takes place by a special exchange. On the site where the "channel to be authenticated" lives (e.g. $mysite), 
 * a redirection is made via $mysite/magic to the zot endpoint of the remote site ($remotesite) with special GET parameters.
 *
 * The endpoint is typically  https://$remotesite/post - or whatever was specified as the callback url in prior communications
 * (we will bootstrap an address and fetch a zot info packet if possible where no prior communications exist)
 *
 * Four GET parameters are supplied:
 *
 ** auth => the urlencoded webbie (channel@host.domain) of the channel requesting access
 ** dest => the desired destination URL (urlencoded)
 ** sec  => a random string which is also stored on $mysite for use during the verification phase. 
 ** version => the zot revision
 *
 * When this packet is received, an "auth-check" zot message is sent to $mysite.
 * (e.g. if $_GET['auth'] is foobar@podunk.edu, a zot packet is sent to the podunk.edu zot endpoint, which is typically /post)
 * If no information has been recorded about the requesting identity a zot information packet will be retrieved before
 * continuing.
 * 
 * The sender of this packet is an arbitrary/random site channel. The recipients will be a single recipient corresponding
 * to the guid and guid_sig we have associated with the requesting auth identity
 *
 *
 *    {
 *      "type":"auth_check",
 *      "sender":{
 *        "guid":"kgVFf_...",
 *        "guid_sig":"PT9-TApz...",
 *        "url":"http:\/\/podunk.edu",
 *        "url_sig":"T8Bp7j..."
 *      },
 *      "recipients":{
 *        {
 *        "guid":"ZHSqb...",
 *        "guid_sig":"JsAAXi..."
 *        }
 *      }
 *      "callback":"\/post",
 *      "version":1,
 *      "secret":"1eaa661",
 *      "secret_sig":"eKV968b1..."
 *    }
 *
 *
 * auth_check messages MUST use encapsulated encryption. This message is sent to the origination site, which checks the 'secret' to see 
 * if it is the same as the 'sec' which it passed originally. It also checks the secret_sig which is the secret signed by the 
 * destination channel's private key and base64url encoded. If everything checks out, a json packet is returned:
 *
 *    { 
 *      "success":1, 
 *      "confirm":"q0Ysovd1u..."
 *      "service_class":(optional)
 *      "level":(optional)
 *    }
 *
 * 'confirm' in this case is the base64url encoded RSA signature of the concatenation of 'secret' with the
 * base64url encoded whirlpool hash of the requestor's guid and guid_sig; signed with the source channel private key. 
 * This prevents a man-in-the-middle from inserting a rogue success packet. Upon receipt and successful 
 * verification of this packet, the destination site will redirect to the original destination URL and indicate a successful remote login. 
 * Service_class can be used by cooperating sites to provide different access rights based on account rights and subscription plans. It is 
 * a string whose contents are not defined by protocol. Example: "basic" or "gold". 
 *
 *
 *
 */
	
	if(array_key_exists('auth',$_REQUEST)) {

		$ret = array('success' => false, 'message' => '');

		logger('mod_zot: auth request received.');
		$address = $_REQUEST['auth'];
		$desturl = $_REQUEST['dest'];
		$sec     = $_REQUEST['sec'];
		$version = $_REQUEST['version'];
		$test    = ((x($_REQUEST,'test')) ? intval($_REQUEST['test']) : 0);

		// They are authenticating ultimately to the site and not to a particular channel.
		// Any channel will do, providing it's currently active. We just need to have an 
		// identity to attach to the packet we send back. So find one. 

		$c = q("select * from channel where not ( channel_pageflags & %d ) limit 1",
			intval(PAGE_REMOVED)
		);

		if(! $c) {
			// nobody here
			logger('mod_zot: auth: unable to find a response channel');
			if($test) {
				$ret['message'] .= 'no local channels found.' . EOL;
				json_return_and_die($ret);
			}

			goaway($desturl);
		}

		// Try and find a hubloc for the person attempting to auth
		$x = q("select * from hubloc left join xchan on xchan_hash = hubloc_hash where hubloc_addr = '%s' order by hubloc_id desc limit 1",
			dbesc($address)
		);

		if(! $x) {
			// finger them if they can't be found. 
			$ret = zot_finger($address,null);
			if($ret['success']) {
				$j = json_decode($ret['body'],true);
				if($j)
					import_xchan($j);
				$x = q("select * from hubloc left join xchan on xchan_hash = hubloc_hash where hubloc_addr = '%s' order by hubloc_id desc limit 1",
					dbesc($address)
				);
			}
		}
		if(! $x) {
			logger('mod_zot: auth: unable to finger ' . $address);

			if($test) {
				$ret['message'] .= 'no hubloc found for ' . $address . ' and probing failed.' . EOL;
				json_return_and_die($ret);
			}

			goaway($desturl);
		}

		logger('mod_zot: auth request received from ' . $x[0]['hubloc_addr'] ); 

		// check credentials and access

		// If they are already authenticated and haven't changed credentials, 
		// we can save an expensive network round trip and improve performance.

		$remote = remote_user();
		$result = null;
		$remote_service_class = '';
		$remote_level = 0;
		$remote_hub = $x[0]['hubloc_url'];

		// Also check that they are coming from the same site as they authenticated with originally.

		$already_authed = ((($remote) && ($x[0]['hubloc_hash'] == $remote) && ($x[0]['hubloc_url'] === $_SESSION['remote_hub'])) ? true : false); 

		$j = array();

		if(! $already_authed) {

			// Auth packets MUST use ultra top-secret hush-hush mode - e.g. the entire packet is encrypted using the site private key
			// The actual channel sending the packet ($c[0]) is not important, but this provides a generic zot packet with a sender
			// which can be verified
 
			$p = zot_build_packet($c[0],$type = 'auth_check', array(array('guid' => $x[0]['hubloc_guid'],'guid_sig' => $x[0]['hubloc_guid_sig'])), $x[0]['hubloc_sitekey'], $sec);
			if($test) {
				$ret['message'] .= 'auth check packet created using sitekey ' . $x[0]['hubloc_sitekey'] . EOL;
				$ret['message'] .= 'packet contents: ' . $p . EOL;
			}

			$result = zot_zot($x[0]['hubloc_callback'],$p);


			if(! $result['success']) {
				logger('mod_zot: auth_check callback failed.');
				if($test) {
					$ret['message'] .= 'auth check request to your site returned .' . print_r($result, true) . EOL;
					json_return_and_die($ret);
				}

				goaway($desturl);
			}
			$j = json_decode($result['body'],true);
			if(! $j) {
				logger('mod_zot: auth_check json data malformed.');
				if($test) {
					$ret['message'] .= 'json malformed: ' . $result['body'] . EOL;
					json_return_and_die($ret);
				}
			}				
		}

		if($test) {
			$ret['message'] .= 'auth check request returned .' . print_r($j, true) . EOL;
		}	

		if($already_authed || $j['success']) {
			if($j['success']) {
				// legit response, but we do need to check that this wasn't answered by a man-in-middle
				if(! rsa_verify($sec . $x[0]['xchan_hash'],base64url_decode($j['confirm']),$x[0]['xchan_pubkey'])) {
					logger('mod_zot: auth: final confirmation failed.');
					if($test) {
						$ret['message'] .= 'final confirmation failed. ' . $sec . print_r($j,true) . print_r($x[0],true);
						json_return_and_die($ret);
					}
						
					goaway($desturl);
				}
				if(array_key_exists('service_class',$j))
					$remote_service_class = $j['service_class'];
				if(array_key_exists('level',$j))
					$remote_level = $j['level'];
			}
			// everything is good... maybe
			if(local_user()) {

				// tell them to logout if they're logged in locally as anything but the target remote account
				// in which case just shut up because they don't need to be doing this at all.

				if($a->channel['channel_hash'] != $x[0]['xchan_hash']) {
					logger('mod_zot: auth: already authenticated locally as somebody else.');
					notice( t('Remote authentication blocked. You are logged into this site locally. Please logout and retry.') . EOL);
					if($test) {
						$ret['message'] .= 'already logged in locally with a conflicting identity.' . EOL;
						json_return_and_die($ret);
					}

				}
				goaway($desturl);
			}
			// log them in

			if($test) {
				$ret['success'] = true;
				$ret['message'] .= 'Authentication Success!' . EOL;
				json_return_and_die($ret);
			}


			$_SESSION['authenticated'] = 1;
			$_SESSION['visitor_id'] = $x[0]['xchan_hash'];
			$_SESSION['my_address'] = $address;
			$_SESSION['remote_service_class'] = $remote_service_class;
			$_SESSION['remote_level'] = $remote_level;
			$_SESSION['remote_hub'] = $remote_hub;
			
			$arr = array('xchan' => $x[0], 'url' => $desturl, 'session' => $_SESSION);
			call_hooks('magic_auth_success',$arr);
			$a->set_observer($x[0]);
			require_once('include/security.php');
			$a->set_groups(init_groups_visitor($_SESSION['visitor_id']));
			info(sprintf( t('Welcome %s. Remote authentication successful.'),$x[0]['xchan_name']));
			logger('mod_zot: auth success from ' . $x[0]['xchan_addr']); 

		} else {
			if($test) {
				$ret['message'] .= 'auth failure. ' . print_r($_REQUEST,true) . print_r($j,true) . EOL;
				json_return_and_dir($ret);
			}

			logger('mod_zot: magic-auth failure - not authenticated: ' . $x[0]['xchan_addr']);
			q("update hubloc set hubloc_status =  (hubloc_status | %d ) where hubloc_id = %d ",
				intval(HUBLOC_RECEIVE_ERROR),
				intval($x[0]['hubloc_id'])
			);
		}

		// FIXME - we really want to save the return_url in the session before we visit rmagic.
		// This does however prevent a recursion if you visit rmagic directly, as it would otherwise send you back here again. 
		// But z_root() probably isn't where you really want to go. 

		if($test) {
			$ret['message'] .= 'auth failure fallthrough ' . print_r($_REQUEST,true) . print_r($j,true) . EOL;
			json_return_and_dir($ret);
		}

		if(strstr($desturl,z_root() . '/rmagic'))
			goaway(z_root());

		goaway($desturl);
	}
	return;
}


/**
 * @function post_post(&$a)
 *     zot communications and messaging
 *
 *     Sender HTTP posts to this endpoint ($site/post typically) with 'data' parameter set to json zot message packet.
 *     This packet is optionally encrypted, which we will discover if the json has an 'iv' element.
 *     $contents => array( 'alg' => 'aes256cbc', 'iv' => initialisation vector, 'key' => decryption key, 'data' => encrypted data);
 *     $contents->iv and $contents->key are random strings encrypted with this site's RSA public key and then base64url encoded.
 *     Currently only 'aes256cbc' is used, but this is extensible should that algorithm prove inadequate.
 *
 *     Once decrypted, one will find the normal json_encoded zot message packet. 
 * 
 * Defined packet types are: notify, purge, refresh, auth_check, ping, and pickup 
 *
 * Standard packet: (used by notify, purge, refresh, and auth_check)
 *
 * {
 *  "type": "notify",
 *  "sender":{
 *       "guid":"kgVFf_1...",
 *       "guid_sig":"PT9-TApzp...",
 *       "url":"http:\/\/podunk.edu",
 *       "url_sig":"T8Bp7j5...",
 *    },
 *  "recipients": { optional recipient array },
 *  "callback":"\/post",
 *  "version":1,
 *  "secret":"1eaa...",
 *  "secret_sig": "df89025470fac8..."
 * }
 * 
 * Signature fields are all signed with the sender channel private key and base64url encoded.
 * Recipients are arrays of guid and guid_sig, which were previously signed with the recipients private 
 * key and base64url encoded and later obtained via channel discovery. Absence of recipients indicates
 * a public message or visible to all potential listeners on this site.
 *
 * "pickup" packet:
 * The pickup packet is sent in response to a notify packet from another site
 * 
 * {
 *  "type":"pickup",
 *  "url":"http:\/\/example.com",
 *  "callback":"http:\/\/example.com\/post",
 *  "callback_sig":"teE1_fLI...",
 *  "secret":"1eaa...",
 *  "secret_sig":"O7nB4_..."
 * }
 *
 * In the pickup packet, the sig fields correspond to the respective data element signed with this site's system 
 * private key and then base64url encoded.
 * The "secret" is the same as the original secret from the notify packet. 
 *
 * If verification is successful, a json structure is returned
 * containing a success indicator and an array of type 'pickup'.
 * Each pickup element contains the original notify request and a message field whose contents are 
 * dependent on the message type
 *
 * This JSON array is AES encapsulated using the site public key of the site that sent the initial zot pickup packet.
 * Using the above example, this would be example.com.
 * 
 * 
 * {
 * "success":1,
 * "pickup":{
 *   "notify":{
 *     "type":"notify",
 *     "sender":{
 *       "guid":"kgVFf_...",
 *       "guid_sig":"PT9-TApz...",
 *       "url":"http:\/\/z.podunk.edu",
 *       "url_sig":"T8Bp7j5D..."
 *     },
 *     "callback":"\/post",
 *     "version":1,
 *     "secret":"1eaa661..."
 *   },
 *   "message":{
 *     "type":"activity",
 *     "message_id":"10b049ce384cbb2da9467319bc98169ab36290b8bbb403aa0c0accd9cb072e76@podunk.edu",
 *     "message_top":"10b049ce384cbb2da9467319bc98169ab36290b8bbb403aa0c0accd9cb072e76@podunk.edu",
 *     "message_parent":"10b049ce384cbb2da9467319bc98169ab36290b8bbb403aa0c0accd9cb072e76@podunk.edu",
 *     "created":"2012-11-20 04:04:16",
 *     "edited":"2012-11-20 04:04:16",
 *     "title":"",
 *     "body":"Hi Nickordo",
 *     "app":"",
 *     "verb":"post",
 *     "object_type":"",
 *     "target_type":"",
 *     "permalink":"",
 *     "location":"",
 *     "longlat":"",
 *     "owner":{
 *       "name":"Indigo",
 *       "address":"indigo@podunk.edu",
 *       "url":"http:\/\/podunk.edu",
 *       "photo":{
 *         "mimetype":"image\/jpeg",
 *         "src":"http:\/\/podunk.edu\/photo\/profile\/m\/5"
 *       },
 *       "guid":"kgVFf_...",
 *       "guid_sig":"PT9-TAp...",
 *     },
 *     "author":{
 *       "name":"Indigo",
 *       "address":"indigo@podunk.edu",
 *       "url":"http:\/\/podunk.edu",
 *       "photo":{
 *         "mimetype":"image\/jpeg",
 *         "src":"http:\/\/podunk.edu\/photo\/profile\/m\/5"
 *       },
 *       "guid":"kgVFf_...",
 *       "guid_sig":"PT9-TAp..."
 *     }
 *   }
 * }
 *} 
 *
 * Currently defined message types are 'activity', 'mail', 'profile' and 'channel_sync', which each have 
 * different content schemas.
 *
 * Ping packet:
 * A ping packet does not require any parameters except the type. It may or may not be encrypted.
 * 
 * {
 *  "type": "ping"
 * }
 * 
 * On receipt of a ping packet a ping response will be returned:
 *
 * {
 *   "success" : 1,
 *   "site" {
 *       "url":"http:\/\/podunk.edu",
 *       "url_sig":"T8Bp7j5...",
 *       "sitekey": "-----BEGIN PUBLIC KEY-----
 *                  MIICIjANBgkqhkiG9w0BAQE..."
 *    }
 * }
 * 
 * The ping packet can be used to verify that a site has not been re-installed, and to 
 * initiate corrective action if it has. The url_sig is signed with the site private key
 * and base64url encoded - and this should verify with the enclosed sitekey. Failure to
 * verify indicates the site is corrupt or otherwise unable to communicate using zot.
 * This return packet is not otherwise verified, so should be compared with other
 * results obtained from this site which were verified prior to taking action. For instance
 * if you have one verified result with this signature and key, and other records for this 
 * url which have different signatures and keys, it indicates that the site was re-installed
 * and corrective action may commence (remove or mark invalid any entries with different
 * signatures).
 * If you have no records which match this url_sig and key - no corrective action should
 * be taken as this packet may have been returned by an imposter.  
 *
 */

	
function post_post(&$a) {

	logger('mod_zot: ' . print_r($_REQUEST,true), LOGGER_DEBUG);

	$encrypted_packet = false;
	$ret = array('success' => false);

	$data = json_decode($_REQUEST['data'],true);

	logger('mod_zot: data: ' . print_r($data,true), LOGGER_DATA);

	/**
	 * Many message packets will arrive encrypted. The existence of an 'iv' element 
	 * tells us we need to unencapsulate the AES-256-CBC content using the site private key
	 */

	if(array_key_exists('iv',$data)) {
		$encrypted_packet = true;
		$data = crypto_unencapsulate($data,get_config('system','prvkey'));
		logger('mod_zot: decrypt1: ' . $data, LOGGER_DATA);
		$data = json_decode($data,true);
	}

	if(! $data) {

		// possible Bleichenbacher's attack, just treat it as a 
		// message we have no handler for. It should fail a bit 
		// further along with "no hub". Our public key is public
		// knowledge. There's no reason why anybody should get the 
		// encryption wrong unless they're fishing or hacking. If 
		// they're developing and made a goof, this can be discovered 
		// in the logs of the destination site. If they're fishing or 
		// hacking, the bottom line is we can't verify their hub. 
		// That's all we're going to tell them.

		$data = array('type' => 'bogus');
	}

	logger('mod_zot: decoded data: ' . print_r($data,true), LOGGER_DATA);

	$msgtype = ((array_key_exists('type',$data)) ? $data['type'] : '');

	if($msgtype === 'ping') {

		// Useful to get a health check on a remote site.
		// This will let us know if any important communication details
		// that we may have stored are no longer valid, regardless of xchan details.
 
		$ret['success'] = true;
		$ret['site'] = array();
		$ret['site']['url'] = z_root();
		$ret['site']['url_sig'] = base64url_encode(rsa_sign(z_root(),get_config('system','prvkey')));
		$ret['site']['sitekey'] = get_config('system','pubkey');
		json_return_and_die($ret);
	}

	if($msgtype === 'pickup') {

		/**
		 * The 'pickup' message arrives with a tracking ID which is associated with a particular outq_hash
		 * First verify that that the returned signatures verify, then check that we have an outbound queue item
		 * with the correct hash.
		 * If everything verifies, find any/all outbound messages in the queue for this hubloc and send them back
		 *
		 */

		if((! $data['secret']) || (! $data['secret_sig'])) {
			$ret['message'] = 'no verification signature';
			logger('mod_zot: pickup: ' . $ret['message'], LOGGER_DEBUG);
			json_return_and_die($ret);
		}
		$r = q("select distinct hubloc_sitekey from hubloc where hubloc_url = '%s' and hubloc_callback = '%s' and hubloc_sitekey != '' group by hubloc_sitekey ",
			dbesc($data['url']),
			dbesc($data['callback'])
		);
		if(! $r) {
			$ret['message'] = 'site not found';
			logger('mod_zot: pickup: ' . $ret['message']);
			json_return_and_die($ret);
		}

		foreach ($r as $hubsite) {

			// verify the url_sig
			// If the server was re-installed at some point, there could be multiple hubs with the same url and callback.
			// Only one will have a valid key.

			$forgery = true;
			$secret_fail = true;

			$sitekey = $hubsite['hubloc_sitekey'];

			logger('mod_zot: Checking sitekey: ' . $sitekey);

			if(rsa_verify($data['callback'],base64url_decode($data['callback_sig']),$sitekey)) {
				$forgery = false;
			}
			if(rsa_verify($data['secret'],base64url_decode($data['secret_sig']),$sitekey)) {
				$secret_fail = false;
			}
			if((! $forgery) && (! $secret_fail))
				break;
		}

		if($forgery) {
			$ret['message'] = 'possible site forgery';
			logger('mod_zot: pickup: ' . $ret['message']);
			json_return_and_die($ret);
		}

		if($secret_fail) {
			$ret['message'] = 'secret validation failed';
			logger('mod_zot: pickup: ' . $ret['message']);
			json_return_and_die($ret);
		}

		/**
		 * If we made it to here, the signatures verify, but we still don't know if the tracking ID is valid.
		 * It wouldn't be an error if the tracking ID isn't found, because we may have sent this particular
		 * queue item with another pickup (after the tracking ID for the other pickup  was verified). 
		 */

		$r = q("select outq_posturl from outq where outq_hash = '%s' and outq_posturl = '%s' limit 1",
			dbesc($data['secret']),
			dbesc($data['callback'])
		);
		if(! $r) {
			$ret['message'] = 'nothing to pick up';
			logger('mod_zot: pickup: ' . $ret['message']);
			json_return_and_die($ret);
		}

		/**
		 * Everything is good if we made it here, so find all messages that are going to this location
		 * and send them all.
		 */

		$r = q("select * from outq where outq_posturl = '%s'",
			dbesc($data['callback'])
		);
		if($r) {
			$ret['success'] = true;
			$ret['pickup'] = array();
			foreach($r as $rr) {
				$ret['pickup'][] = array('notify' => json_decode($rr['outq_notify'],true),'message' => json_decode($rr['outq_msg'],true));

				$x = q("delete from outq where outq_hash = '%s' limit 1",
					dbesc($rr['outq_hash'])
				);
			}
		}

		$encrypted = crypto_encapsulate(json_encode($ret),$sitekey);
		json_return_and_die($encrypted);

		/** pickup: end */
	}




	/**
	 * All other message types require us to verify the sender. This is a generic check, so we 
	 * will do it once here and bail if anything goes wrong.
	 */

	if(array_key_exists('sender',$data)) {
		$sender = $data['sender'];
	}	

	/** Check if the sender is already verified here */

	$hub = zot_gethub($sender);

	if(! $hub) {

		/** Have never seen this guid or this guid coming from this location. Check it and register it. */

		// (!!) this will validate the sender
		$result = zot_register_hub($sender);

		if((! $result['success']) || (! ($hub = zot_gethub($sender)))) {
			$ret['message'] = 'Hub not available.';
			logger('mod_zot: no hub');
			json_return_and_die($ret);
		}
	}


	// Update our DB to show when we last communicated successfully with this hub
	// This will allow us to prune dead hubs from using up resources

	$r = q("update hubloc set hubloc_connected = '%s' where hubloc_id = %d limit 1",
		dbesc(datetime_convert()),
		intval($hub['hubloc_id'])
	);

	/** 
	 * This hub has now been proven to be valid.
	 * Any hub with the same URL and a different sitekey cannot be valid.
	 * Get rid of them (mark them deleted). There's a good chance they were re-installs.
	 *
	 */

	q("update hubloc set hubloc_flags = ( hubloc_flags | %d ) where hubloc_url = '%s' and hubloc_sitekey != '%s' ",
		intval(HUBLOC_FLAGS_DELETED),
		dbesc($hub['hubloc_url']),
		dbesc($hub['hubloc_sitekey'])
	);

	// TODO: check which hub is primary and take action if mismatched

	if(array_key_exists('recipients',$data))
		$recipients = $data['recipients'];


	if($msgtype === 'auth_check') {

		/**
		 * Requestor visits /magic/?dest=somewhere on their own site with a browser
		 * magic redirects them to $destsite/post [with auth args....]
		 * $destsite sends an auth_check packet to originator site
		 * The auth_check packet is handled here by the originator's site 
		 * - the browser session is still waiting
		 * inside $destsite/post for everything to verify
		 * If everything checks out we'll return a token to $destsite
		 * and then $destsite will verify the token, authenticate the browser
		 * session and then redirect to the original destination.
		 * If authentication fails, the redirection to the original destination
		 * will still take place but without authentication.
		 */
		logger('mod_zot: auth_check', LOGGER_DEBUG);

		if(! $encrypted_packet) {
			logger('mod_zot: auth_check packet was not encrypted.');
			$ret['message'] .= 'no packet encryption' . EOL;
			json_return_and_die($ret);
		}
		
		$arr = $data['sender'];
		$sender_hash = base64url_encode(hash('whirlpool',$arr['guid'] . $arr['guid_sig'], true));

		// garbage collect any old unused notifications
		q("delete from verify where type = 'auth' and created < UTC_TIMESTAMP() - INTERVAL 10 MINUTE");

		$y = q("select xchan_pubkey from xchan where xchan_hash = '%s' limit 1",
			dbesc($sender_hash)
		);

		// We created a unique hash in mod/magic.php when we invoked remote auth, and stored it in
		// the verify table. It is now coming back to us as 'secret' and is signed by a channel at the other end.
		// First verify their signature. We will have obtained a zot-info packet from them as part of the sender
		// verification. 

		if((! $y) || (! rsa_verify($data['secret'],base64url_decode($data['secret_sig']),$y[0]['xchan_pubkey']))) {
			logger('mod_zot: auth_check: sender not found or secret_sig invalid.');
			$ret['message'] .= 'sender not found or sig invalid ' . print_r($y,true) . EOL;
			json_return_and_die($ret);
		}

		// There should be exactly one recipient, the original auth requestor

		$ret['message'] .= 'recipients ' . print_r($recipients,true) . EOL;

		if($data['recipients']) {

			$arr = $data['recipients'][0];
			$recip_hash = base64url_encode(hash('whirlpool',$arr['guid'] . $arr['guid_sig'], true));
			$c = q("select channel_id, channel_account_id, channel_prvkey from channel where channel_hash = '%s' limit 1",
				dbesc($recip_hash)
			);
			if(! $c) {
				logger('mod_zot: auth_check: recipient channel not found.');
				$ret['message'] .= 'recipient not found.' . EOL;
				json_return_and_die($ret);
			}

			$confirm = base64url_encode(rsa_sign($data['secret'] . $recip_hash,$c[0]['channel_prvkey']));

			// This additionally checks for forged sites since we already stored the expected result in meta
			// and we've already verified that this is them via zot_gethub() and that their key signed our token

			$z = q("select id from verify where channel = %d and type = 'auth' and token = '%s' and meta = '%s' limit 1",
				intval($c[0]['channel_id']),
				dbesc($data['secret']),
				dbesc($data['sender']['url'])
			);
			if(! $z) {
				logger('mod_zot: auth_check: verification key not found.');
				$ret['message'] .= 'verification key not found' . EOL;
				json_return_and_die($ret);
			}
			$r = q("delete from verify where id = %d limit 1",
				intval($z[0]['id'])
			);

			$u = q("select account_service_class from account where account_id = %d limit 1",
				intval($c[0]['channel_account_id'])
			);

			logger('mod_zot: auth_check: success', LOGGER_DEBUG);
			$ret['success'] = true;
			$ret['confirm'] = $confirm;
			if($u && $u[0]['account_service_class'])
				$ret['service_class'] = $u[0]['account_service_class'];
			json_return_and_die($ret);

		}
		json_return_and_die($ret);
	}


	if($msgtype === 'purge') {
		if($recipients) {
			// basically this means "unfriend"
			foreach($recipients as $recip) {
				$r = q("select channel.*,xchan.* from channel 
					left join xchan on channel_hash = xchan_hash
					where channel_guid = '%s' and channel_guid_sig = '%s' limit 1",
					dbesc($recip['guid']),
					dbesc($recip['guid_sig'])
				);
				if($r) {
					$r = q("select abook_id from abook where uid = %d and abook_xchan = '%s' limit 1",
						intval($r[0]['channel_id']),
						dbesc(base64url_encode(hash('whirlpool',$sender['guid'] . $sender['guid_sig'], true)))
					);
					if($r) {
						contact_remove($r[0]['channel_id'],$r[0]['abook_id']);
					}
				}
			}
		}
		else {
			// Unfriend everybody - basically this means the channel has committed suicide
			$arr = $data['sender'];
			$sender_hash = base64url_encode(hash('whirlpool',$arr['guid'] . $arr['guid_sig'], true));
		
			require_once('include/Contact.php');
			remove_all_xchan_resources($sender_hash);	

			$ret['success'] = true;
			json_return_and_die($ret);

		}
	}

	if($msgtype === 'refresh') {

		// remote channel info (such as permissions or photo or something)
		// has been updated. Grab a fresh copy and sync it.

		if($recipients) {

			// This would be a permissions update, typically for one connection

			foreach($recipients as $recip) {	
				$r = q("select channel.*,xchan.* from channel 
					left join xchan on channel_hash = xchan_hash
					where channel_guid = '%s' and channel_guid_sig = '%s' limit 1",
					dbesc($recip['guid']),
					dbesc($recip['guid_sig'])
				);

				$x = zot_refresh(array(
						'xchan_guid'     => $sender['guid'], 
						'xchan_guid_sig' => $sender['guid_sig'],
						'hubloc_url'     => $sender['url']
				),$r[0]);
			}
		}
		else {

			// system wide refresh
				
			$x = zot_refresh(array(
				'xchan_guid'     => $sender['guid'], 
				'xchan_guid_sig' => $sender['guid_sig'],
				'hubloc_url'     => $sender['url']
			),null);
		}
		$ret['success'] = true;
		json_return_and_die($ret);
	}

	if($msgtype === 'notify') {
		$async = get_config('system','queued_fetch');

		if($async) {
			// add to receive queue
			// qreceive_add($data);
		}
		else {
			$x = zot_fetch($data);
			$ret['delivery_report'] = $x;
		}

		$ret['success'] = true;
		json_return_and_die($ret);

	}


	// catchall
	json_return_and_die($ret);


}

