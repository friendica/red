Fresh - The Freaking REd Shell
===============================

This will only work on Unix/Linux. If the readline module is installed, we will
use that for input, otherwise we will just read from stdin and write to stdout.

Commands are processed sequentially until the command "exit", "quit", or end 
of file is reached.


Commands:

* version
	Report current fresh version

* login email_address
	Prompts for a password, and authenticates email_address as the current 
user.

* finger channel_address
	performs a lookup of channel_address and reports the result.

* channel channel_nickname
	switches the current channel to the channel with nickname specified.

* conn [id1 id2 ...]
	With no arguments lists all connections of the current channel, with an id.
If IDs are provided the connections details of each will be displayed. 



	

