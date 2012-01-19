<?php
	/**
	 *  cache api
	 */
	 
	class Cache {
		public static function get($key){
			$r = q("SELECT `v` FROM `cache` WHERE `k`='%s'",
				dbesc($key)
			);
			
			if (count($r)) return $r[0]['v'];
			return null;
		}
		
		public static function set($key,$value) {
			q("INSERT INTO `cache` VALUES ('%s','%s','%s')",
				dbesc($key),
				dbesc($value),
				dbesc(datetime_convert()));
		}
		
		public static function clear(){
			q("DELETE FROM `cache` WHERE `updated` < '%s'",
				dbesc(datetime_convert('UTC','UTC',"now - 30 days")));			
		}
		
	}
	 
