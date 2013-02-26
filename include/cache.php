<?php /** @file */

	/**
	 *  cache api
	 */
	 
	class Cache {
		public static function get($key){
			$r = q("SELECT `v` FROM `cache` WHERE `k`='%s' limit 1",
				dbesc($key)
			);
			
			if (count($r)) return $r[0]['v'];
			return null;
		}
		
		public static function set($key,$value) {

			q("REPLACE INTO `cache` (`k`,`v`,`updated`) VALUES ('%s','%s','%s')",
					dbesc($key),
					dbesc($value),
					dbesc(datetime_convert()));
		}

		
/* 
 *
 * Leaving this legacy code temporaily to see how REPLACE fares 
 * as opposed to non-atomic checks when faced with fast moving key duplication.
 * As a MySQL extension it isn't portable, but we're not yet very portable.
 */

/*
 *			$r = q("SELECT * FROM `cache` WHERE `k`='%s' limit 1",
 *				dbesc($key)
 *			);
 *			if(count($r)) {
 *				q("UPDATE `cache` SET `v` = '%s', `updated = '%s' WHERE `k` = '%s' limit 1",
 *					dbesc($value),
 *					dbesc(datetime_convert()),
 *					dbesc($key));
 *			}
 *			else {
 *				q("INSERT INTO `cache` (`k`,`v`,`updated`) VALUES ('%s','%s','%s')",
 *					dbesc($key),
 *					dbesc($value),
 *					dbesc(datetime_convert()));
 *			}
 *		}
 */

		
		public static function clear(){
			q("DELETE FROM `cache` WHERE `updated` < '%s'",
				dbesc(datetime_convert('UTC','UTC',"now - 30 days")));			
		}
		
	}
	 
