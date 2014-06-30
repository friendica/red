[size=large][b]The Red Matrix FAQ[/b][/size]

[ul]
[*][b]Is there a way to change the Admin account?[/b]
[*][b]Is there a way to have multiple administrators?[/b]
Yes, but it's a bit messy at the moment as it is not yet exposed in the UI.  To make an account an administrative account, 
one needs to add 4096 to the account_roles entry in the account table of the database.  Likewise, to remove administrative permissions, 
one must subtract 4096 from the account roles.

[*][b]I can log in, but there are no posts or webpages[/b]

Most likely, your item table has crashed.  Run the MySQL command [code]repair table item;[/code]

[*][b]Login doesn't work, immediately after login, the page reloads and I'm logged out[/b]

Most likely, your session table has crashed.  Run the MySQL command [code]repair table session;[/code]

[*][b]When I switch theme, I sometimes get elements of one theme superimposed on top of the other[/b]

a) view/tpl/smarty3 isn't writeable by the webserver.  Make it so.

b) You're using Midori, or with certain themes, Konqueror in KHTML mode.  

[b]My network tab won't load, it appears to be caused by a photo or video[/b]

Your PHP memory limit is too low.  Increase the size of the memory_limit directive in your php.ini 

Contrary to popular belief, the number of users on a hub doesn't make any difference to the required memory limit, rather, the content
of an individuals matrix counts.  Streams with lots of photos and video require more memory than streams with lots of text.

[*] [b]I have no communication with anybody[/b]

You're listening on port 443, but do not have a valid SSL certificate.  Note this applies even if your baseurl is http.
Don't listen on port 443 if you cannot use it.  It is strongly recommended to solve this problem by installing a browser
valid SSL certificate rather than disabling port 443.

[*]
[b]What do I need to do when moving my hub to a different server[/b]

1) Git clone on the new server.  Repeat the process for any custom themes, and addons.
2) Copy .htconfig.php
3) Rsync everything in store/
4) Rsync everything in custom/ (this will only exist if you have custom modules)
5) Dump and restore DB.

[/ul]

Return to the [zrl=[baseurl]/help/main]Main documentation page[/zrl]
