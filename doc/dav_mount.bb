[b]Mounting As A Filesystem[/b]

To install your cloud directory as a filesystem, you first need davfs2 installed.  99% of the time, this will be included in your distributions repositories.  In Debian

[code]apt-get install davfs2[/code]

If you want to let normal users mount the filesystem

[code] dpkg-reconfigure davfs2[/code]

and select &quot;yes&quot; at the prompt.

Now you need to add any user you want to be able to mount dav to the davfs2 group

[code]usermod -aG davfs2 &lt;DesktopUser&gt;[/code]

Edit /etc/fstab

[code]nano /etc/fstab[/code]

 to include your cloud directory by adding

[code]
example.com/cloud/ /mount/point davfs user,noauto,uid=&lt;DesktopUser&gt;,file_mode=600,dir_mode=700 0 1
[/code]

Where example.com is the URL of your hub, /mount/point is the location you want to mount the cloud, and &lt;DesktopUser&gt; is the user you log in to one your computer.  Note that if you are mounting as a normal user (not root) the mount point must be in your home directory.

For example, if I wanted to mount my cloud to a directory called 'cloud' in my home directory, and my username was bob, my fstab would be 

[code]example.com/cloud/ /home/bob/cloud davfs user,noauto,uid=bob,file_mode=600,dir_mode=700 0 1[/code]

Now, create the mount point.

[code]mkdir /home/bob/cloud[/code]

and also create a directory file to store your credentials

[code]mkdir /home/bob/.davfs2[/code]

Create a file called 'secrets'

[code]nano /home/bob/.davfs2/secrets[/code]

and add your cloud login credentials

[code]
example.com/cloud &lt;username&gt; &lt;password&gt;
[/code]

Where &lt;username&gt; and &lt;password&gt; are the username and password [i]for your hub[/i].

Don't let this file be writeable by anyone who doesn't need it with

[code]chmod 600 /home/bob/.davfs2/secrets[/code]

Finally, mount the drive.

[code]mount example.com/cloud[/code]

You can now find your cloud at /home/bob/cloud and use it as though it were part of your local filesystem - even if the applications you are using have no dav support themselves.

Return to the [zrl=[baseurl]/help/main]Main documentation page[/zrl]