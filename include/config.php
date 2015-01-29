<?php
/**
 * @file include/config.php
 * @brief Arbitrary configuration storage.
 *
 * Note:
 * Please do not store booleans - convert to 0/1 integer values
 * The get_?config() functions return boolean false for keys that are unset,
 * and this could lead to subtle bugs.
 *
 * Arrays get stored as serialize strings.
 *
 * @todo There are a few places in the code (such as the admin panel) where
 * boolean configurations need to be fixed as of 10/08/2011.
 *
 * - <b>config</b> is used for hub specific configurations. It overrides the
 * configurations from .htconfig file. The storage is of size TEXT.
 * - <b>pconfig</b> is used for channel specific configurations and takes a
 * <i>channel_id</i> as identifier. It stores for example which features are
 * enabled per channel. The storage is of size MEDIUMTEXT.
 * @code $var = get_pconfig(local_channel(), 'category', 'key');@endcode
 * - <b>xconfig</b> is the same as pconfig, except that it uses <i>xchan</i> as
 * an identifier. This is for example for people who do not have a local account.
 * The storage is of size MEDIUMTEXT.
 * @code $observer = $a->get_observer_hash();
 * if ($observer) {
 *     $var = get_xconfig($observer, 'category', 'key');
 * }@endcode
 *
 * - get_config() and set_config() can also be done through the command line tool
 * @ref util/config
 * - get_pconfig() and set_pconfig() can also be done through the command line tool
 * @ref util/pconfig and takes a channel_id as first argument. 
 *
 */

/**
 * @brief Loads the hub's configuration from database to a cached storage.
 *
 * Retrieve a category ($family) of config variables from database to a cached
 * storage in the global $a->config[$family].
 *
 * @param string $family
 *  The category of the configuration value
 */
function load_config($family) {
	global $a;

	if(! array_key_exists($family, $a->config))
		$a->config[$family] = array();

	if(! array_key_exists('config_loaded', $a->config[$family])) {
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

/**
 * @brief Get a particular config variable given the category name ($family)
 * and a key.
 *
 * Get a particular config variable from the given category ($family) and the
 * $key from a cached storage in $a->config[$family]. If a key is found in the
 * DB but does not exist in local config cache, pull it into the cache so we
 * do not have to hit the DB again for this item.
 * 
 * Returns false if not set.
 *
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to query
 * @return mixed Return value or false on error or if not set
 */
function get_config($family, $key) {
	global $a;

	if((! array_key_exists($family, $a->config)) || (! array_key_exists('config_loaded', $a->config[$family])))
		load_config($family);

	if(array_key_exists('config_loaded', $a->config[$family])) {
		if(! array_key_exists($key, $a->config[$family])) {
			return false;		
		}
		return ((! is_array($a->config[$family][$key])) && (preg_match('|^a:[0-9]+:{.*}$|s', $a->config[$family][$key])) 
			? unserialize($a->config[$family][$key])
			: $a->config[$family][$key]
		);
	}
	return false;
}

/**
 * @brief Returns a value directly from the database configuration storage.
 *
 * This function queries directly the database and bypasses the chached storage
 * from get_config($family, $key).
 *
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to query
 * @return mixed
 */
function get_config_from_storage($family, $key) {
	$ret = q("SELECT * FROM config WHERE cat = '%s' AND k = '%s' LIMIT 1",
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}

/**
 * @brief Sets a configuration value for the hub.
 *
 * Stores a config value ($value) in the category ($family) under the key ($key).
 *
 * Please do not store booleans - convert to 0/1 integer values!
 *
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to set
 * @param mixed $value
 *  The value to store in the configuration
 * @return mixed
 *  Return the set value, or false if the database update failed
 */
function set_config($family, $key, $value) {
	global $a;

	// manage array value
	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	if(get_config($family, $key) === false || (! get_config_from_storage($family, $key))) {
		$ret = q("INSERT INTO config ( cat, k, v ) VALUES ( '%s', '%s', '%s' ) ",
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret) {
			$a->config[$family][$key] = $value;
			$ret = $value;
		}
		return $ret;
	}

	$ret = q("UPDATE config SET v = '%s' WHERE cat = '%s' AND k = '%s'",
		dbesc($dbvalue),
		dbesc($family),
		dbesc($key)
	);

	if($ret) {
		$a->config[$family][$key] = $value;
		$ret = $value;
	}
	return $ret;
}

/**
 * @brief Deletes the given key from the hub's configuration database.
 *
 * Removes the configured value from the stored cache in $a->config[$family]
 * and removes it from the database.
 *
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to delete
 * @return mixed
 */
function del_config($family, $key) {
	global $a;
	$ret = false;

	if(array_key_exists($family, $a->config) && array_key_exists($key, $a->config[$family]))
		unset($a->config[$family][$key]);
		$ret = q("DELETE FROM config WHERE cat = '%s' AND k = '%s'",
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}


/**
 * @brief Loads all configuration values of a channel into a cached storage.
 *
 * All configuration values of the given channel are stored in global cache
 * which is available under the global variable $a->config[$uid].
 *
 * @param string $uid
 *  The channel_id
 * @return void|false Nothing or false if $uid is false
 */
function load_pconfig($uid) {
	global $a;

	if($uid === false)
		return false;

	if(! array_key_exists($uid, $a->config))
		$a->config[$uid] = array();

	$r = q("SELECT * FROM pconfig WHERE uid = %d",
		intval($uid)
	);

	if($r) {
		foreach($r as $rr) {
			$k = $rr['k'];
			$c = $rr['cat'];
			if(! array_key_exists($c, $a->config[$uid])) {
				$a->config[$uid][$c] = array();
				$a->config[$uid][$c]['config_loaded'] = true;
			}
			$a->config[$uid][$c][$k] = $rr['v'];
		}
	}
}

/**
 * @brief Get a particular channel's config variable given the category name
 * ($family) and a key.
 *
 * Get a particular channel's config value from the given category ($family)
 * and the $key from a cached storage in $a->config[$uid].
 *
 * Returns false if not set.
 *
 * @param string $uid
 *  The channel_id
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to query
 * @param boolean $instore (deprecated, without function)
 * @return mixed Stored value or false if it does not exist
 */
function get_pconfig($uid, $family, $key, $instore = false) {
//	logger('include/config.php: get_pconfig() deprecated instore param used', LOGGER_DEBUG);
	global $a;

	if($uid === false)
		return false;

	if(! array_key_exists($uid, $a->config))
		load_pconfig($uid);

	if((! array_key_exists($family, $a->config[$uid])) || (! array_key_exists($key, $a->config[$uid][$family])))
		return false;

	return ((! is_array($a->config[$uid][$family][$key])) && (preg_match('|^a:[0-9]+:{.*}$|s', $a->config[$uid][$family][$key])) 
		? unserialize($a->config[$uid][$family][$key])
		: $a->config[$uid][$family][$key]
	);
}

/**
 * @brief Sets a configuration value for a channel.
 *
 * Stores a config value ($value) in the category ($family) under the key ($key)
 * for the channel_id $uid.
 *
 * Please do not store booleans - convert to 0/1 integer values!
 *
 * @param string $uid
 *  The channel_id
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to query
 * @return mixed Stored $value or false
 */
function set_pconfig($uid, $family, $key, $value) {
	global $a;

	// manage array value
	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	if(get_pconfig($uid, $family, $key) === false) {
		if(! array_key_exists($uid, $a->config))
			$a->config[$uid] = array();
		if(! array_key_exists($family, $a->config[$uid]))
			$a->config[$uid][$family] = array();

		// keep a separate copy for all variables which were
		// set in the life of this page. We need this to
		// synchronise channel clones.

		if(! array_key_exists('transient', $a->config[$uid]))
			$a->config[$uid]['transient'] = array();
		if(! array_key_exists($family, $a->config[$uid]['transient']))
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

	$ret = q("UPDATE pconfig SET v = '%s' WHERE uid = %d and cat = '%s' AND k = '%s'",
		dbesc($dbvalue),
		intval($uid),
		dbesc($family),
		dbesc($key)
	);

	// keep a separate copy for all variables which were
	// set in the life of this page. We need this to
	// synchronise channel clones.

	if(! array_key_exists('transient', $a->config[$uid]))
		$a->config[$uid]['transient'] = array();
	if(! array_key_exists($family, $a->config[$uid]['transient']))
		$a->config[$uid]['transient'][$family] = array();

	$a->config[$uid][$family][$key] = $value;
	$a->config[$uid]['transient'][$family][$key] = $value;

	if($ret)
		return $value;
	return $ret;
}

/**
 * @brief Deletes the given key from the channel's configuration.
 *
 * Removes the configured value from the stored cache in $a->config[$uid]
 * and removes it from the database.
 *
 * @param string $uid
 *  The channel_id
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to delete
 * @return mixed
 */
function del_pconfig($uid, $family, $key) {
	global $a;
	$ret = false;

	if(x($a->config[$uid][$family], $key))
		unset($a->config[$uid][$family][$key]);
		$ret = q("DELETE FROM pconfig WHERE uid = %d AND cat = '%s' AND k = '%s'",
		intval($uid),
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}


/**
 * @brief Loads a full xchan's configuration into a cached storage.
 *
 * All configuration values of the given observer hash are stored in global
 * cache which is available under the global variable $a->config[$xchan].
 *
 * @param string $xchan
 *  The observer's hash
 * @return void|false Returns false if xchan is not set
 */
function load_xconfig($xchan) {
	global $a;

	if(! $xchan)
		return false;

	if(! array_key_exists($xchan, $a->config))
		$a->config[$xchan] = array();

	$r = q("SELECT * FROM xconfig WHERE xchan = '%s'",
		dbesc($xchan)
	);

	if($r) {
		foreach($r as $rr) {
			$k = $rr['k'];
			$c = $rr['cat'];
			if(! array_key_exists($c, $a->config[$xchan])) {
				$a->config[$xchan][$c] = array();
				$a->config[$xchan][$c]['config_loaded'] = true;
			}
			$a->config[$xchan][$c][$k] = $rr['v'];
		}
	}
}

/**
 * @brief Get a particular observer's config variable given the category
 * name ($family) and a key.
 *
 * Get a particular observer's config value from the given category ($family)
 * and the $key from a cached storage in $a->config[$xchan].
 *
 * Returns false if not set.
 *
 * @param string $xchan
 *  The observer's hash
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to query
 * @return mixed Stored $value or false if it does not exist
 */
function get_xconfig($xchan, $family, $key) {
	global $a;

	if(! $xchan)
		return false;

	if(! array_key_exists($xchan, $a->config))
		load_xconfig($xchan);

	if((! array_key_exists($family, $a->config[$xchan])) || (! array_key_exists($key, $a->config[$xchan][$family])))
		return false;

	return ((! is_array($a->config[$xchan][$family][$key])) && (preg_match('|^a:[0-9]+:{.*}$|s', $a->config[$xchan][$family][$key])) 
		? unserialize($a->config[$xchan][$family][$key])
		: $a->config[$xchan][$family][$key]
	);
}

/**
 * @brief Sets a configuration value for an observer.
 *
 * Stores a config value ($value) in the category ($family) under the key ($key)
 * for the observer's $xchan hash.
 *
 * Please do not store booleans - convert to 0/1 integer values!
 *
 * @param string $xchan
 *  The observer's hash
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to set
 * @return mixed Stored $value or false
 */
function set_xconfig($xchan, $family, $key, $value) {
	global $a;

	// manage array value
	$dbvalue = ((is_array($value))  ? serialize($value) : $value);
	$dbvalue = ((is_bool($dbvalue)) ? intval($dbvalue)  : $dbvalue);

	if(get_xconfig($xchan, $family, $key) === false) {
		if(! array_key_exists($xchan, $a->config))
			$a->config[$xchan] = array();
		if(! array_key_exists($family, $a->config[$xchan]))
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

	$ret = q("UPDATE xconfig SET v = '%s' WHERE xchan = '%s' and cat = '%s' AND k = '%s'",
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

/**
 * @brief Deletes the given key from the observer's config.
 *
 * Removes the configured value from the stored cache in $a->config[$xchan]
 * and removes it from the database.
 *
 * @param string $xchan
 *  The observer's hash
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to delete
 * @return mixed
 */
function del_xconfig($xchan, $family, $key) {
	global $a;
	$ret = false;

	if(x($a->config[$xchan][$family], $key))
		unset($a->config[$xchan][$family][$key]);
	$ret = q("DELETE FROM xconfig WHERE xchan = '%s' AND cat = '%s' AND k = '%s'",
		dbesc($xchan),
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}
