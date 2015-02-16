ZotSH - v.0.0.2

Client for browsing RedDAVs.

Install
-------

ZotSH requires 'requests'(1).
Please refer to requests docs on how to install it (2)

Extract somewere and launch zotsh.py


Description
-----------

ZotSH is a command line WebDAV client for RedMatrix.
It knows how to magic-auth to remote hubs using Zot.

ZotSH uses 'easywebdav' library (0) with small modifications
to 'zotify' it. (See easywebdav/LICENSE)



Commands
--------

host <hostname>
	Authenticate to 'hostname' and switch to it

cd <dirname|..>
	changhe remote dir


ls [path] [-a] [-l] [-d]
	list remote files in current dir if 'path' not defined
	-a list all, show hidden dot-files
	-l list verbose
	-d list only dirs

exists <path>
	Check existence of 'path'
	
mkdir <name>
	Create directory 'name'

mkdirs <path>
	Create parent directories to path, if they don't exists

rmdir <name>
	Delete directory 'name'

delete <path>
	Delete file 'path'

upload <local_path> [remote_path]
	Upload local file 'local_paht' to 'remote_paht'

download <remote_path> [local_path]
	Download remote file 'remote_path' and save it as 'local_path'

cat <remote_paht>
	Print content of 'remote_path'

pwd
	Print current path

lcd
lpwd
lls
	Local file management

quit
help



Config
------

Create a .zotshrc file in your home or in same folder with zotsh.py:


	[zotsh]
	host = https://yourhost.com/
	username = your_username
	password = your_password


Optionally adds

        verify_ssl = false

to skip verification of ssl certs


Changelog
----------
0.0.2		Fix "CommandNotFound" exception, new 'cat' command

0.0.1		First release


Links
-----

_0 : https://github.com/amnong/easywebdav
_1 : http://docs.python-requests.org/en/latest/
_2 : http://docs.python-requests.org/en/latest/user/install/