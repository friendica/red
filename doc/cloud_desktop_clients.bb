[b]Cloud Desktop Clients[/b]

[b]Windows Clients[/b]

[li][zrl=[baseurl]/help/dav_windows]Windows Internal Client[/zrl][/li]


[b]Linux Clients[/b]

[li][zrl=[baseurl]/help/dav_mount]Command Line as a Filesystem[/zrl][/li]
[li][zrl=[baseurl]/help/dav_dolphin]Dolphin[/zrl][/li]
[li][zrl=[baseurl]/help/dav_konqueror]Konqueror[/zrl][/li]
[li][zrl=[baseurl]/help/dav_nautilus]Nautilus[/zrl][/li]
[li][zrl=[baseurl]/help/dav_nemo]Nemo[/zrl][/li]


[b]Server Notes[/b]

Note: There have been reported issues with clients that use "chunked transfer encoding", which includes Apple iOS services, and also the "AnyClient" and "CyberDuck" tools. These work fine for downloads, but uploads often end up with files of zero size. This is caused by an incorrect implemention of chunked encoding in some current FCGI (fast-cgi) implementations. Apache running with PHP as a module does not have these issues, but when running under FCGI you may need to use alternative clients or use the web uploader. At the time of this writing the issue has been open and no updates provided for at least a year. If you encounter zero size files with other clients, please check the client notes; as there are occasional configuration issues which can also produce these symptoms.  

#include doc/macros/cloud_footer.bb;
