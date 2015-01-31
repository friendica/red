[size=large][b]The Red Matrix FAQ[/b][/size]

[toc]

[h3]Is there a way to change the Admin account?[/h3]
[h3]Is there a way to have multiple administrators?[/h3]
Yes, but it's a bit messy at the moment as it is not yet exposed in the UI.  To make an account an administrative account, 
one needs to add 4096 to the account_roles entry in the account table of the database.  Likewise, to remove administrative permissions, 
one must subtract 4096 from the account roles.

[h3]I can log in, but there are no posts or webpages[/h3]

Most likely, your item table has crashed.  Run the MySQL command [code]repair table item;[/code]

[h3]Login doesn't work, immediately after login, the page reloads and I'm logged out[/h3]

Most likely, your session table has crashed.  Run the MySQL command [code]repair table session;[/code]

[h3]When I switch theme, I sometimes get elements of one theme superimposed on top of the other[/h3]

a) store/[data]/smarty3 isn't writeable by the webserver.  Make it so.

b) You're using Midori, or with certain themes, Konqueror in KHTML mode.  

[b]My network tab won't load, it appears to be caused by a photo or video[/h3]

Your PHP memory limit is too low.  Increase the size of the memory_limit directive in your php.ini 

Contrary to popular belief, the number of users on a hub doesn't make any difference to the required memory limit, rather, the content
of an individuals matrix counts.  Streams with lots of photos and video require more memory than streams with lots of text.

[h3]I have no communication with anybody[/h3]

You're listening on port 443, but do not have a valid SSL certificate.  Note this applies even if your baseurl is http.
Don't listen on port 443 if you cannot use it.  It is strongly recommended to solve this problem by installing a browser
valid SSL certificate rather than disabling port 443.

[h3]How do I update a non-Git install?[/h3]
1) Backup .htconfig.php
2) Backup everything in store/
3) Backup any custom changes in mod/site/ and view/site
3) Delete your existing installation
4) Upload the new version.
5) Upload the new version of themes and addons.
6) Restore everything backed up earlier.

[h3]What do I need to do when moving my hub to a different server[/h3]

1) Git clone on the new server.  Repeat the process for any custom themes, and addons.
2) Rsync .htconfig.php
3) Rsync everything in store/
4) Rsync everything in mod/site/ and view/site (these will only exist if you have custom modules)
5) Dump and restore DB.

[h3]How do I reinstall an existing hub on the same server?[/h3]

1) [code]git reset --hard HEAD[/code] will reset all files to their upstream defaults.  This will not reset any local files that do not also exist upstream.  Eg, if you have local changes to mod/channel.php, this will reset them - but will not reset any changes in mod/site/channel.php
2) If you absolutely must reinstall - for example, if you need to upgrade operating system - follow the steps for moving to a different server, but instead of using rsync, backup and restore some other way.

Do not reinstall a hub with a fresh database and fresh .htconfig.php unless as a very last resort.  Creating a temporary account and ask for help via a support channel for non-trivial reinstalls is preferable to reinstalling fresh.

[h3]How do I set the default homepage for logged out viewers?[/h3]

Use the custom_home addon available in the main addons repository.

[h3]What do the different directory mode settings mean?[/h3]
[code]// Configure how we communicate with directory servers.
// DIRECTORY_MODE_NORMAL     = directory client, we will find a directory (all of your member's queries will be directed elsewhere)
// DIRECTORY_MODE_SECONDARY  = caching directory or mirror (keeps in sync with realm primary [adds significant cron execution time])
// DIRECTORY_MODE_PRIMARY    = main directory server (you do not want this unless you are operating your own realm. one per realm.)
// DIRECTORY_MODE_STANDALONE = "off the grid" or private directory services (only local site members in directory)
[/code]
- The default is NORMAL. This off-loads most directory services to a different server. The server used is the config:system/directory_server. This setting MAY be updated by the code to one of the project secondaries if the current server is unreachable. You should either be in control of this other server, or should trust it to use this setting.
- SECONDARY. This allows your local site to act as a directory server without exposing your member's queries to another server. It requires extra processing time during the cron polling, and is not recommended to be run on a shared web host.
- PRIMARY. This allows you to run a completely separate 'Network' of directory servers with your own Realm. By default, all servers are on the RED_GLOBAL realm unless the config:system/directory_realm setting is overridden. [i]Do not use this unless you have your own directory_realm.[/i]
- STANDALONE. This is like primary, except it's a 'Network' all on it's own without talking to any other servers. Use this if you have only one server and want to be segregated from the Red#Matrix directory listings.

#include doc/macros/main_footer.bb;
