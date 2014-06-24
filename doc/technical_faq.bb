These are common questions with answers which are almost always correct.  Note these aren't the [i]only[/i] possible
answers, they're merely the most probable answers.  90% of the time, these solutions should work.  The other 10% of 
the time is when you should use a support forum.

[b]I can log in, but there are no posts or webpages[/b]

Your item table has crashed.  Run the MySQL command repair table item;

[b]Login doesn't work, immediately after login, the page reloads and I'm logged out[/b]

Your session table has crashed.  Run the MySQL command repair table session;

[b]When I switch theme, I sometimes get elements of one theme superimposed on top of the other[/b]

a) view/tpl/smarty3 isn't writeable by the webserver.

b) You're using Midori.

[b]My network tab won't load, it appears to be caused by a photo or video[/b]

Your PHP memory limit is too low.  Increase the size of the memory_limit directive in your php.ini

[b]I have no communication with anybody[/b]

You're listening on port 443, but do not have a valid SSL certificate.  Note this applies even if your baseurl is http.
Don't listen on port 443 if you cannot use it.

[b]I can't see private resources[/b]

You have disabled third party cookies.

[b]What do I need to do when moving my hub to a different server[/b]

1) Git clone on the new server.  Repeat the process for any custom themes, and addons.
2) Copy .htconfig.php
3) Rsync everything in store/
4) Rsync everything in custom/ (this will only exist if you have custom modules)
5) Dump and restore DB.
