Red development - some useful basic functions
=============================================



* get_account_id()

Returns numeric account_id if authenticated or 0. It is possible to be authenticated and not connected to a channel.

* local_user()

Returns authenticated numeric channel_id if authenticated and connected to a channel or 0. Sometimes referred to as $uid in the code.

* remote_user()

Returns authenticated string hash of Red global identifier, if authenticated via remote auth, or an empty string.

* get_app()

Returns the global app structure ($a).

* App::get_observer()

(App:: is usually assigned to the global $a), so $a->get_observer() or get_app()->get_observer() - returns an xchan structure representing the current viewer if authenticated (locally or remotely).

* get_config($family,$key), get_pconfig($uid,$family,$key)

Returns the config setting for $family and $key or false if unset. 

* set_config($family,$key,$value), set_pconfig($uid,$family,$key,$value)

Sets the value of config setting for $family and $key to $value. Returns $value. The config versions operate on system-wide settings. The pconfig versions get/set the values for a specific integer uid (channel_id).  

* dbesc()

Always escape strings being used in DB queries. This function returns the escaped string. Integer DB parameters should all be proven integers by wrapping with intval()

* q($sql,$var1...)

Perform a DB query with the SQL statement $sql. printf style arguments %s and %d are replaced with variable arguments, which should each be appropriately dbesc() or intval(). SELECT queries return an array of results or false if SQL or DB error. Other queries return true if the command was successful or false if it wasn't. 

* t($string)

Returns the translated variant of $string for the current language or $string (default 'en' language) if the language is unrecognised or a translated version of the string does not exist.

* x($var), $x($array,$key)

Shorthand test to see if variable $var is set and is not empty. Tests vary by type. Returns false if $var or $key is not set.
If variable is set, returns 1 if has 'non-zero' value, otherwise returns 0. -- e.g. x('') or x(0) returns 0;



