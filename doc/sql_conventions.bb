[h1]SQL Conventions[/h1]
[b]Intro[/b]
The following common SQL conventions appear throughout the code in many places. We use a simple DBA (DataBase Abstraction layer) to handle differences between databases. Please be sure to use only standards-compliant SQL.

[b]Rule One[/b]
Worth Repeating: Don't use non-standard SQL. This goes for addons as well. If you do use non-standard SQL, and the dba funcs are insufficient, do a if()/switch() or similar for all currently supported databases. Currently nothing red# does requires non-standard SQL.

[b]Using a format string[/b]
[li]Uses sprintf()
To be written
[code]// Example
$r = q("SELECT * FROM profile WHERE uid = %d",
	local_channel()
);
[/code][/li]

[b]Checking bit flags in a where clause[/b]
[li]You must explicitly convert integers to booleans. The easiest way to do this is to compare to 0.
[code]// Example
$r = q("SELECT abook_id, abook_flags, abook_my_perms, abook_their_perms, xchan_hash, xchan_photo_m, xchan_name, xchan_url from abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d and not (abook_flags & %d)>0 ",
	intval($uid),
	intval(ABOOK_FLAG_SELF)
);
[/code]
[/li]
[li]Turning off a flag
[code]$y = q("update xchan set xchan_flags = (xchan_flags & ~%d) where (xchan_flags & %d)>0 and xchan_hash = '%s'",
	intval(XCHAN_FLAGS_ORPHAN),
	intval(XCHAN_FLAGS_ORPHAN),
	dbesc($rr['hubloc_hash'])
);[/code]
[/li]
[li]Turning on a flag
[code]$y = q("update xchan set xchan_flags = (xchan_flags | %d) where xchan_hash = '%s'",
	intval(XCHAN_FLAGS_ORPHAN),
	dbesc($rr['hubloc_hash'])
);[/code]
[/li]

[b]Using relative times (INTERVALs)[/b]
[li]Sometimes you want to compare something, like less than x days old.
[code]// Example
$r = q("SELECT * FROM abook left join xchan on abook_xchan = xchan_hash 
	WHERE abook_dob > %s + interval %s and abook_dob < %s + interval %s",
	db_utcnow(), db_quoteinterval('7 day'),
	db_utcnow(), db_quoteinterval('14 day')
);[/code]
[/li]
[b]Paged results[/b]
[li]To be written
[code]// Example
$r = q("SELECT * FROM mail WHERE uid=%d AND $sql_extra ORDER BY created DESC LIMIT %d OFFSET %d",
	intval(api_user()),
	intval($count), intval($start)
);[/code][/li]

[b]NULL dates[/b]
[li]To be written
[code]// Example
$r = q("DELETE FROM mail WHERE expires != '%s' AND expires < %s ",
	dbesc(NULL_DATE),
	db_utcnow()
);[/code][/li]

[b]Storing binary data[/b]
[li]To be written
[code]// Example
$x = q("update photo set data = '%s', height = %d, width = %d where resource_id = '%s' and uid = %d and scale = 0",
	dbescbin($ph->imageString()),
	intval($height),
	intval($width),
	dbesc($resource_id),
	intval($page_owner_uid)
);[/code][/li]

[b]Current timestamp[/b]
[li][code]// Example
$randfunc = db_getfunc('rand');
$r = q("select xchan_url from xchan left join hubloc on hubloc_hash = xchan_hash where hubloc_connected > %s - interval %s order by $randfunc limit 1",
	db_utcnow(), db_quoteinterval('30 day')
);[/code][/li]

[b]SQL Function and Operator Abstraction[/b]
[li]Sometimes the same function or operator has a different name/symbol in each database. You use db_getfunc('funcname') to look them up. The string is [i]not[/i] case-sensitive; do [i]not[/i] include parens.
[code]// Example
$randfunc = db_getfunc('rand');
$r = q("select xchan_url from xchan left join hubloc on hubloc_hash = xchan_hash where hubloc_connected > %s - interval %s order by $randfunc limit 1",
	db_utcnow(), db_quoteinterval('30 day')
);[/code][/li]

#include doc/macros/main_footer.bb;