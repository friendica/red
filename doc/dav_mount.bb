[b]Mounting As A Filesystem[/b]

To install your cloud directory as a filesystem, you first need davfs2 installed.  99% of the time, this will be included in your distributions repositories.  In Debian

[code]apt-get install davfs2[/code]

If you want to let normal users mount the filesystem

[code] dpkg-reconfigure davfs2[/code]

and select &quot;yes&quot; at the prompt.

Now you need to add any user you want to be able to mount dav to the davfs2 group

[code]usermod -aG davfs2 &lt;DesktopUser&gt;[/code]

[b]Note:[/b] on some systems the user group may be different, i.e. - "network" 
on Arch Linux. If in doubt, check the davfs documentation for your 
particular OS.

Edit /etc/fstab

[code]nano /etc/fstab[/code]

 to include your cloud directory by adding

[code]
[baseurl]/cloud/ /mount/point davfs user,noauto,uid=&lt;DesktopUser&gt;,file_mode=600,dir_mode=700 0 1
[/code]

Where [baseurl] is the URL of your hub, /mount/point is the location you want to mount the cloud, and &lt;DesktopUser&gt; is the user you log in to one your computer.  Note that if you are mounting as a normal user (not root) the mount point must be in your home directory.

For example, if I wanted to mount my cloud to a directory called 'cloud' in my home directory, and my username was bob, my fstab would be 

[code][baseurl]/cloud/ /home/bob/cloud davfs user,noauto,uid=bob,file_mode=600,dir_mode=700 0 1[/code]

Now, create the mount point.

[code]mkdir /home/bob/cloud[/code]

and also create a directory file to store your credentials

[code]mkdir /home/bob/.davfs2[/code]

Create a file called 'secrets'

[code]nano /home/bob/.davfs2/secrets[/code]

and add your cloud login credentials

[code]
[baseurl]/cloud &lt;username&gt; &lt;password&gt;
[/code]

Where &lt;username&gt; and &lt;password&gt; are the username and password [i]for your hub[/i].

Don't let this file be writeable by anyone who doesn't need it with

[code]chmod 600 /home/bob/.davfs2/secrets[/code]

Finally, mount the drive.

[code]mount [baseurl]/cloud[/code]

You can now find your cloud at /home/bob/cloud and use it as though it were part of your local filesystem - even if the applications you are using have no dav support themselves.

[b]Troubleshooting[/b]

With some webservers and certain configurations, you may find davfs2 creating files with 0 bytes file size where other clients work just fine.  This is generally caused by cache and locks.  If you are affected by this issue, you need to edit your davfs2 configuration.

[code]nano /etc/davfs2/davfs2.conf[/code]

Your distribution will provide a sample configuration, and this file should already exist, however, most of it will be commented out with a # at the beginning of the line.  

First step is to remove locks.

Edit the use_locks line so it reads [code]use_locks 0[/code].

Unmount your file system, remount your file system, and try copying over a file from the command line.  Note you should copy a new file, and not overwrite an old one for this test.  Leave it a minute or two then do [code]ls -l -h[/code] and check the file size of your new file is still greater than 0 bytes.  If it is, stop there, and do nothing else.

If that still doesn't work, disable the cache.  Note that this has a performance impact so should only be done if disabling locks didn't solve your problem.  Edit the cache_size and set it to [code]cache_size 0[/code] and also set file_refresh to [code]file_refresh 0[/code].  Unmount your filesystem, remount your file system, and test it again.

If it [i]still[/i] doesn't work, there is one more thing you can try.  (This one is caused by a bug in older versions of dav2fs itself, so updating to a new version may also help).  Enable weak etag dropping by setting [code]drop_weak_etags 1[/code].  Unmount and remount your filesystem to apply the changes.

#include doc/macros/cloud_footer.bb;

