[b]Troubleshooting[/b]

[li][zrl=[baseurl]/help/problems-following-an-update]Problems following an update[/zrl][/li]

When reporting issues, please try to provide as much detail as may be necessary for developers to reproduce the issue and provide the complete text of all error messages.

The system logfile is an extremely useful resource for tracking down things that go wrong. This can be enabled in the admin/log configuration page. A loglevel setting of LOGGER_DEBUG is preferred for stable production sites. Most things that go wrong with communications or storage are listed here. A setting of LOGGER_DATA provides [b]much[/b] more detail, but may fill your disk. In either case we recommend the use of logrotate on your operating system to cycle logs and discard older entries. 

At the bottom of your .htconfig.php file are several lines (commented out) which enable PHP error logging. This reports issues with code syntax and executing the code and is the first place you should look for issues which result in a "white screen" or blank page. This is typically the result of code/syntax problems. 
Database errors are reported to the system logfile, but we've found it useful to have a file in your top-level directory called dbfail.out which [b]only[/b] collects database related issues. If the file exists and is writable, database errors will be logged to it as well as to the system logfile.

In the case of "500" errors, the issues may often be logged in your webserver logs, often /var/log/apache2/error.log or something similar. Consult your operating system documentation. 

We encourage you to try to the best of your abilities to use these logs combined with the source code in your possession to troubleshoot issues and find their cause. The community is often able to help, but only you have access to your site logfiles and it is considered a security risk to share them.   


If a code issue has been uncovered, please report it on the project bugtracker (https://github.com/friendica/red/issues). Again provide as much detail as possible to avoid us going back and forth asking questions about your configuration or how to duplicate the problem, so that we can get right to the problem and figure out what to do about it. You are also welcome to offer your own solutions and submit patches. In fact we encourage this as we are all volunteers and have little spare time available. The more people that help, the easier the workload for everybody. It's OK if your solution isn't perfect. Every little bit helps and perhaps we can improve on it.  

#include doc/macros/troubleshooting_footer.bb;
#include doc/macros/main_footer.bb;

