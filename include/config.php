<?php /** @file */

/**
 *
 * Arbitrary configuration storage
 * Note:
 * Please do not store booleans - convert to 0/1 integer values
 * The get_?config() functions return boolean false for keys that are unset,
 * and this could lead to subtle bugs.
 *
 * There are a few places in the code (such as the admin panel) where boolean
 * configurations need to be fixed as of 10/08/2011.
 */


// retrieve a "family" of config variables from database to cached storage

function load_config($family) {
	global $a;

	if(! array_key_exists($family,$a->config))
		$a->config[$family] = array();

	if(! array_key_exists('config_loaded',$a->config[$family])) {

		$r = q("SELECT * FROM config WHERE cat = '%s'", dbesc($family));

		if($r !== false) {
			if($r) {
				foreach($r as $rr) {
					$k = $rr['k'];
					$a->config[$family][$k] = $rr['v'];
				}
			}
			$a->config[$family]['config_loaded'] = true;
		}
	} 
}

// get a particular config variable given the family name
// and key. Returns false if not set.
// $instore is only used by the set_config function
// to determine if the key already exists in the DB
// If a key is found in the DB but doesn't exist in
// local config cache, pull it into the cache so we don't have
// to hit the DB again for this item.


function get_config($family, $key) {

	global $a;

	if((! array_key_exists($family,$a->config)) || (! array_key_exists('config_loaded',$a->config[$family])))
		load_config($family);

	if(array_key_exists('config_loaded',$a->config[$family])) {
		if(! array_key_exists($key,$a->config[$family])) {
			return false;		
		}
		return ((! is_array($a->config[$family][$key])) && (preg_match('|^a:[0-9]+:{.*}$|s', $a->config[$family][$key])) 
			? unserialize($a->config[$family][$key])
			: $a->config[$family][$key]
		);
	}
	return false;
}

function get_config_from_storage($family,$key) {
	$ret = q("select * from config where cat = '%s' and k = '%s' limit 1",
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}



// Store a config value ($value) in the category ($family)
// under the key ($key)
// Return the value, or false if the database update failed

function set_config($family,$key,$value) {
	global $a;
	// manage array value
	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	if(get_config($family,$key) === false || (! get_config_from_storage($family,$key))) {
		$a->config[$family][$key] = $value;

		$ret = q("INSERT INTO config ( cat, k, v ) VALUES ( '%s', '%s', '%s' ) ",
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret)
			return $value;
		return $ret;
	}

	$ret = q("UPDATE config SET v = '%s' WHERE cat = '%s' AND k = '%s' LIMIT 1",
		dbesc($dbvalue),
		dbesc($family),
		dbesc($key)
	);

	$a->config[$family][$key] = $value;

	if($ret)
		return $value;
	return $ret;
}

function del_config($family,$key) {
	global $a;
	if(array_key_exists($family,$a->config) && array_key_exists($key,$a->config[$family]))
		unset($a->config[$family][$key]);
	$ret = q("DELETE FROM config WHERE cat = '%s' AND k = '%s' LIMIT 1",
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}


function load_pconfig($uid,$family = '') {
	global $a;

	if($uid === false)
		return false;

	if(! array_key_exists($uid,$a->config))
		$a->config[$uid] = array();

	// family is no longer used - load entire user config

	$r = q("SELECT * FROM `pconfig` WHERE `uid` = %d",
		intval($uid)
	);

	if($r) {
		foreach($r as $rr) {
			$k = $rr['k'];
			$c = $rr['cat'];
			if(! array_key_exists($c,$a->config[$uid])) {
				$a->config[$uid][$c] = array();
				$a->config[$uid][$c]['config_loaded'] = true;
			}
			$a->config[$uid][$c][$k] = $rr['v'];
		}
	} 
}




function get_pconfig($uid,$family, $key, $instore = false) {

	global $a;

	if($uid === false)
		return false;

	if(! array_key_exists($uid,$a->config))
		load_pconfig($uid);

	if((! array_key_exists($family,$a->config[$uid])) || (! array_key_exists($key,$a->config[$uid][$family])))
		return false;
		
	return ((! is_array($a->config[$uid][$family][$key])) && (preg_match('|^a:[0-9]+:{.*}$|s', $a->config[$uid][$family][$key])) 
		? unserialize($a->config[$uid][$family][$key])
		: $a->config[$uid][$family][$key]
	);
}

function set_pconfig($uid,$family,$key,$value) {

	global $a;


	// manage array value
	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	if(get_pconfig($uid,$family,$key) === false) {
		if(! array_key_exists($uid,$a->config))
			$a->config[$uid] = array();
		if(! array_key_exists($family,$a->config[$uid]))
			$a->config[$uid][$family] = array();

		// keep a separate copy for all variables which were
		// set in the life of this page. We need this to
		// synchronise channel clones.

		if(! array_key_exists('transient',$a->config[$uid]))
			$a->config[$uid]['transient'] = array();
		if(! array_key_exists($family,$a->config[$uid]['transient']))
			$a->config[$uid]['transient'][$family] = array();

		$a->config[$uid][$family][$key] = $value;
		$a->config[$uid]['transient'][$family][$key] = $value;

		$ret = q("INSERT INTO pconfig ( uid, cat, k, v ) VALUES ( %d, '%s', '%s', '%s' ) ",
			intval($uid),
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret)
			return $value;
		return $ret;
	}

	$ret = q("UPDATE pconfig SET v = '%s' WHERE uid = %d and cat = '%s' AND k = '%s' LIMIT 1",
		dbesc($dbvalue),
		intval($uid),
		dbesc($family),
		dbesc($key)
	);

	// keep a separate copy for all variables which were
	// set in the life of this page. We need this to
	// synchronise channel clones.

	if(! array_key_exists('transient',$a->config[$uid]))
		$a->config[$uid]['transient'] = array();
	if(! array_key_exists($family,$a->config[$uid]['transient']))
		$a->config[$uid]['transient'][$family] = array();

	$a->config[$uid][$family][$key] = $value;
	$a->config[$uid]['transient'][$family][$key] = $value;

	if($ret)
		return $value;
	return $ret;
}


function del_pconfig($uid,$family,$key) {

	global $a;
	if(x($a->config[$uid][$family],$key))
		unset($a->config[$uid][$family][$key]);
	$ret = q("DELETE FROM pconfig WHERE uid = %d AND cat = '%s' AND k = '%s' LIMIT 1",
		intval($uid),
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}



function load_xconfig($xchan,$family = '') {
	global $a;

	if(! $xchan)
		return false;

	if(! array_key_exists($xchan,$a->config))
		$a->config[$xchan] = array();

	// family is no longer used. Entire config is loaded

	$r = q("SELECT * FROM `xconfig` WHERE `xchan` = '%s'",
		dbesc($xchan)
	);

	if($r) {
		foreach($r as $rr) {
			$k = $rr['k'];
			$c = $rr['cat'];
			if(! array_key_exists($c,$a->config[$xchan])) {
				$a->config[$xchan][$c] = array();
				$a->config[$xchan][$c]['config_loaded'] = true;
			}
			$a->config[$xchan][$c][$k] = $rr['v'];
		}
	} 
}




function get_xconfig($xchan,$family, $key) {

	global $a;

	if(! $xchan)
		return false;

	if(! array_key_exists($xchan,$a->config))
		load_xconfig($xchan);

	if((! array_key_exists($family,$a->config[$xchan])) || (! array_key_exists($key,$a->config[$xchan][$family])))
		return false;

	return ((! is_array($a->config[$xchan][$family][$key])) && (preg_match('|^a:[0-9]+:{.*}$|s', $a->config[$xchan][$family][$key])) 
		? unserialize($a->config[$xchan][$family][$key])
		: $a->config[$xchan][$family][$key]
	);

}


function set_xconfig($xchan,$family,$key,$value) {

	global $a;

	// manage array value
	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	if(get_xconfig($xchan,$family,$key) === false) {
		if(! array_key_exists($xchan,$a->config))
			$a->config[$xchan] = array();
		if(! array_key_exists($family,$a->config[$xchan]))
			$a->config[$xchan][$family] = array();

		$a->config[$xchan][$family][$key] = $value;
		$ret = q("INSERT INTO xconfig ( xchan, cat, k, v ) VALUES ( '%s', '%s', '%s', '%s' ) ",
			dbesc($xchan),
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret)
			return $value;
		return $ret;
	}

	$ret = q("UPDATE xconfig SET v = '%s' WHERE xchan = '%s' and cat = '%s' AND k = '%s' LIMIT 1",
		dbesc($dbvalue),
		dbesc($xchan),
		dbesc($family),
		dbesc($key)
	);

	$a->config[$xchan][$family][$key] = $value;

	if($ret)
		return $value;
	return $ret;

}


function del_xconfig($xchan,$family,$key) {

	global $a;
	if(x($a->config[$xchan][$family],$key))
		unset($a->config[$xchan][$family][$key]);
	$ret = q("DELETE FROM `xconfig` WHERE `xchan` = '%s' AND `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($xchan),
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}



