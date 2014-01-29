**Installing The Cloud as a Filesystem on Linux**

To install your cloud directory as a filesystem, you first need davfs2 installed.  99% of the time, this will be included in your distributions repositories.  In Debian

`apt-get install davfs2`

If you want to let normal users mount the filesystem

`dpkg-reconfigure davfs2`

and select "yes" at the prompt.

Now you need to add any user you want to be able to mount dav to the davfs2 group

`usermod -aG davfs2 <DesktopUser>`

Edit /etc/fstab

`nano /etc/fstab`

to include your cloud directory by adding

`example.com/cloud/ /mount/point davfs user,noauto,uid=<DesktopUser>,file_mode=600,dir_mode=700 0 1`

Where example.com is the URL of your hub, /mount/point is the location you want to mount the cloud, and <DesktopUser> is the user you log in to one your computer.  Note that if you are mounting as a normal user (not root) the mount point must be in your home directory.

For example, if I wanted to mount my cloud to a directory called 'cloud' in my home directory, and my username was bob, my fstab would be

`example.com/cloud/ /home/bob/cloud davfs user,noauto,uid=bob,file_mode=600,dir_mode=700 0 1`

Now, create the mount point.

`mkdir /home/bob/cloud`

and also create a directory file to store your credentials

`mkdir /home/bob/.davfs2`

Create a file called 'secrets'

`nano /home/bob/.davfs2/secrets`

and add your cloud login credentials

`example.com/cloud <username> <password>`


Where <username> and <password> are the username and password for your hub.

Don't let this file be writeable by anyone who doesn't need it with

`chmod 600 /home/bob/.davfs2/secrets`

Finally, mount the drive.

`mount example.com/cloud`

You can now find your cloud at /home/bob/cloud and use it as though it were part of your local filesystem - even if the applications you are using have no dav support themselves.
