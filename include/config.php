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
	$r = q("SELECT * FROM config WHERE cat = '%s'", dbesc($family));
	if($r) {
		foreach($r as $rr) {
			$k = $rr['k'];
			if ($family === 'config') {
				$a->config[$k] = $rr['v'];
			} else {
				$a->config[$family][$k] = $rr['v'];
			}
		}
	} else if ($family != 'config') {
		// Negative caching
		$a->config[$family] = "!<unset>!";
	}
}

// get a particular config variable given the family name
// and key. Returns false if not set.
// $instore is only used by the set_config function
// to determine if the key already exists in the DB
// If a key is found in the DB but doesn't exist in
// local config cache, pull it into the cache so we don't have
// to hit the DB again for this item.


function get_config($family, $key, $instore = false) {

	global $a;

	if(! $instore) {
		// Looking if the whole family isn't set
		if(isset($a->config[$family])) {
			if($a->config[$family] === '!<unset>!') {
				return false;
			}
		}

		if(isset($a->config[$family][$key])) {
			if($a->config[$family][$key] === '!<unset>!') {
				return false;
			}
			return $a->config[$family][$key];
		}
	}
	$ret = q("SELECT `v` FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($family),
		dbesc($key)
	);
	if(count($ret)) {
		// manage array value
		$val = (preg_match("|^a:[0-9]+:{.*}$|s", $ret[0]['v'])?unserialize( $ret[0]['v']):$ret[0]['v']);
		$a->config[$family][$key] = $val;
		return $val;
	}
	else {
		$a->config[$family][$key] = '!<unset>!';
	}
	return false;
}

// Store a config value ($value) in the category ($family)
// under the key ($key)
// Return the value, or false if the database update failed


function set_config($family,$key,$value) {
	global $a;
	// manage array value
	$dbvalue = (is_array($value)?serialize($value):$value);
	$dbvalue = (is_bool($dbvalue) ? intval($dbvalue) : $dbvalue);
	if(get_config($family,$key,true) === false) {
		$a->config[$family][$key] = $value;
		$ret = q("INSERT INTO `config` ( `cat`, `k`, `v` ) VALUES ( '%s', '%s', '%s' ) ",
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret)
			return $value;
		return $ret;
	}

	$ret = q("UPDATE `config` SET `v` = '%s' WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
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
	if(x($a->config[$family],$key))
		unset($a->config[$family][$key]);
	$ret = q("DELETE FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}


function load_pconfig($uid,$family) {
	global $a;
	$r = q("SELECT * FROM `pconfig` WHERE `cat` = '%s' AND `uid` = %d",
		dbesc($family),
		intval($uid)
	);
	if(count($r)) {
		foreach($r as $rr) {
			$k = $rr['k'];
			$a->config[$uid][$family][$k] = $rr['v'];
		}
	} else if ($family != 'config') {
		// Negative caching
		$a->config[$uid][$family] = "!<unset>!";
	}
}




function get_pconfig($uid,$family, $key, $instore = false) {

	global $a;

	if(! $instore) {
		// Looking if the whole family isn't set
		if(isset($a->config[$uid][$family])) {
			if($a->config[$uid][$family] === '!<unset>!') {
				return false;
			}
		}

		if(isset($a->config[$uid][$family][$key])) {
			if($a->config[$uid][$family][$key] === '!<unset>!') {
				return false;
			}
			return $a->config[$uid][$family][$key];
		}
	}

	$ret = q("SELECT `v` FROM `pconfig` WHERE `uid` = %d AND `cat` = '%s' AND `k` = '%s' LIMIT 1",
		intval($uid),
		dbesc($family),
		dbesc($key)
	);

	if(count($ret)) {
		$val = (preg_match("|^a:[0-9]+:{.*}$|s", $ret[0]['v'])?unserialize( $ret[0]['v']):$ret[0]['v']);
		$a->config[$uid][$family][$key] = $val;
		return $val;
	}
	else {
		$a->config[$uid][$family][$key] = '!<unset>!';
	}
	return false;
}





// Same as above functions except these are for personal config storage and take an
// additional $uid argument.


function set_pconfig($uid,$family,$key,$value) {

	global $a;

	// manage array value
	$dbvalue = (is_array($value)?serialize($value):$value);

	if(get_pconfig($uid,$family,$key,true) === false) {
		$a->config[$uid][$family][$key] = $value;
		$ret = q("INSERT INTO `pconfig` ( `uid`, `cat`, `k`, `v` ) VALUES ( %d, '%s', '%s', '%s' ) ",
			intval($uid),
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret) 
			return $value;
		return $ret;
	}
	$ret = q("UPDATE `pconfig` SET `v` = '%s' WHERE `uid` = %d AND `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($dbvalue),
		intval($uid),
		dbesc($family),
		dbesc($key)
	);

	$a->config[$uid][$family][$key] = $value;

	if($ret)
		return $value;
	return $ret;
}


function del_pconfig($uid,$family,$key) {

	global $a;
	if(x($a->config[$uid][$family],$key))
		unset($a->config[$uid][$family][$key]);
	$ret = q("DELETE FROM `pconfig` WHERE `uid` = %d AND `cat` = '%s' AND `k` = '%s' LIMIT 1",
		intval($uid),
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}



function load_xconfig($xchan,$family) {
	global $a;
	$r = q("SELECT * FROM `xconfig` WHERE `cat` = '%s' AND `xchan` = '%s'",
		dbesc($family),
		dbesc($xchan)
	);
	if(count($r)) {
		foreach($r as $rr) {
			$k = $rr['k'];
			$a->config[$xchan][$family][$k] = $rr['v'];
		}
	} else if ($family != 'config') {
		// Negative caching
		$a->config[$xchan][$family] = "!<unset>!";
	}
}




function get_xconfig($xchan,$family, $key, $instore = false) {

	global $a;

	if(! $instore) {
		// Looking if the whole family isn't set
		if(isset($a->config[$xchan][$family])) {
			if($a->config[$xchan][$family] === '!<unset>!') {
				return false;
			}
		}

		if(isset($a->config[$xchan][$family][$key])) {
			if($a->config[$xchan][$family][$key] === '!<unset>!') {
				return false;
			}
			return $a->config[$xchan][$family][$key];
		}
	}

	$ret = q("SELECT `v` FROM `xconfig` WHERE `xchan` = '%s' AND `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($xchan),
		dbesc($family),
		dbesc($key)
	);

	if(count($ret)) {
		$val = (preg_match("|^a:[0-9]+:{.*}$|s", $ret[0]['v'])?unserialize( $ret[0]['v']):$ret[0]['v']);
		$a->config[$xchan][$family][$key] = $val;
		return $val;
	}
	else {
		$a->config[$xchan][$family][$key] = '!<unset>!';
	}
	return false;
}


function set_xconfig($xchan,$family,$key,$value) {

	global $a;

	// manage array value
	$dbvalue = (is_array($value)?serialize($value):$value);

	if(get_xconfig($xchan,$family,$key,true) === false) {
		$a->config[$xchan][$family][$key] = $value;
		$ret = q("INSERT INTO `xconfig` ( `xchan`, `cat`, `k`, `v` ) VALUES ( '%s', '%s', '%s', '%s' ) ",
			dbesc($xchan),
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret) 
			return $value;
		return $ret;
	}
	$ret = q("UPDATE `xconfig` SET `v` = '%s' WHERE `xchan` = '%s' AND `cat` = '%s' AND `k` = '%s' LIMIT 1",
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



