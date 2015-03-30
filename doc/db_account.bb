[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]account_id[/td][td]table index[/td][td]int(10) unsigned[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]account_parent[/td][td]for hierarchical accounts, the account_id of the parent to this one, if account_parent = account_id, this is the top level account[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]account_default_channel[/td][td]channel_id of channel to connect on login[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]account_salt[/td][td]complexity token for account_password[/td][td]char(32)[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]
[tr][td]account_password[/td][td]hashed password for this account[/td][td]char(255)[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]
[tr][td]account_email[/td][td]essentially the login ID, although it is usually possible to login with a channel address[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]account_external[/td][td]Currently unused[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]account_language[/td][td]default language (closest available browser-accept language when account was created)[/td][td]char(16)[/td][td]NO[/td][td][/td][td]en[/td][td]
[/td][/tr]
[tr][td]account_created[/td][td]timestamp of account creation[/td][td]datetime[/td][td]NO[/td][td][/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]account_lastlog[/td][td]timestamp of last login (or daily update if "remember me" is in effect)[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]account_flags[/td][td]see notes[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]account_roles[/td][td]see notes[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]account_reset[/td][td]verification token for password reset[/td][td]char(255)[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]
[tr][td]account_expires[/td][td]timestamp when account expires and will be deleted[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]account_expire_notified[/td][td]timestamp of last warning of account expiration[/td][td]datetime[/td][td]NO[/td][td][/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]account_service_class[/td][td]service class for this account, determines what if any limits/restrictions are in place[/td][td]char(32)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]account_level[/td][td]future use[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]account_password_changed[/td][td]timestamp of last password change - to limit account deletion for 48 hours to prevent malicious activity[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[/table]

Notes:



/**
 * Account Flags
 */

define ( 'ACCOUNT_OK',           0x0000 );
define ( 'ACCOUNT_UNVERIFIED',   0x0001 );
define ( 'ACCOUNT_BLOCKED',      0x0002 );
define ( 'ACCOUNT_EXPIRED',      0x0004 );
define ( 'ACCOUNT_REMOVED',      0x0008 );
define ( 'ACCOUNT_PENDING',      0x0010 );

/**
 * Account roles
 */

define ( 'ACCOUNT_ROLE_ALLOWCODE', 0x0001 ); // 1 - this account can create content with PHP/Javascript
define ( 'ACCOUNT_ROLE_SYSTEM',    0x0002 ); // 2 - this is the special system account
define ( 'ACCOUNT_ROLE_DEVELOPER', 0x0004 );
define ( 'ACCOUNT_ROLE_ADMIN',     0x1000 ); // 4096 - this account is an administrator


Return to [zrl=[baseurl]/help/database]database documentation[/zrl]