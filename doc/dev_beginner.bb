[h2]You want to contribute code?[/h2]
[b]...and don't know how to start to...[/b]
[list]
[*] debug the red#matrix (php on the webserver),
[*] contribute code to the project,
[*] optionally - do it all from inside a virtual machine
[/list]
This manual was tested for Debian (Wheezy) as virtual machine on Lubuntu (Ubuntu 14.0) as host.

Content

[toc]

[h2]Install a Virtual Machine (KVM)[/h2]

[url=https://wiki.debian.org/KVM]Here[/url] the installation guide for Linux Debian.
The summary:
[list=1]
[*] install KVM
[code]# apt-get install qemu-kvm libvirt-bin[/code]
[*] add yourself to the group libvirt [code]# adduser <youruser> libvirt[/code]
[*] install gui to manage virtual machines [code]# apt-get install virt-manager[/code]
[*] download an operating system to run inside the vm ([url=http://ftp.nl.debian.org/debian/dists/wheezy/main/installer-amd64/current/images/netboot/mini.iso]mini.iso[/url])
[*] start the virt manager
- create new virtual machine (click on icon)
- choose your iso image (just downloaded) as installation source
- optional: configure the new vm: ram, cpu's,...
- start virtual machine > result: linux debian starts in a new window.
[*] (optional) avoid network errors after restart of host os
[code]# virsh net-start default
# virsh net-autostart default[/code]
[/list]


[h2]Install Apache Webserver[/h2]

Open a terminal and make yourself root
[code]su -l[/code]

Create the standard group for the Apache webserver
[code]groupadd www-data[/code]
might exist already

[code]usermod -a -G www-data www-data[/code]

Check if the system is really up to date
[code]apt-get update
apt-get upgrade[/code]

Optional restart services after installation
[code]reboot[/code]

If you restarted, make yourself root
[code]su -l[/code]

Install Apache: [code]
apt-get install apache2 apache2-doc apache2-utils[/code]

Open webbrowser on PC and check [url=localhost]localhost[/url]
Should show you a page like "It works"

(Source [url=http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#]http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#[/url])


[h2]Install PHP, MySQL, phpMyAdmin[/h2]

[h3]PHP, MySQL[/h3]

[code]su -l
apt-get install libapache2-mod-php5 php5 php-pear php5-xcache php5-curl php5-mcrypt php5-xdebug
apt-get install php5-mysql
apt-get install mysql-server mysql-client[/code]
enter and note the mysql passwort

Optional since its already enabled during phpmyadmin setup
[code]
php5enmod mcrypt
[/code]

[h3]phpMyAdmin[/h3]

Install php myadmin
[code]apt-get install phpmyadmin[/code]

Configuring phpmyadmin
- Select apache2 (hint: use the tab key to select)
- Configure database for phpmyadmin with dbconfig-common?: Choose Yes

(Source #^[url=http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#]http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#[/url])

[h3]Enable rewrite[/h3]

The default installation of Apache2 comes with mod_rewrite installed. To check whether this is the case, verify the existence of /etc/apache2/mods-available/rewrite.load

[code]
root@debian /var/www $ nano /etc/apache2/mods-available/rewrite.load
[/code]

 (You should find the content: LoadModule rewrite_module /usr/lib/apache2/modules/mod_rewrite.so)
To enable and load mod_rewrite, do the rest of steps.
Create a symbolic link in /etc/apache2/mods-enabled

[code]
cd /var/www
root@debian /var/www $ a2enmod rewrite
[/code]

Then open up the following file, and replace every occurrence of "AllowOverride None" with "AllowOverride all".

[code]
root@debian /var/www $nano /etc/apache2/apache2.conf
[/code]
or
[code]
root@debian:/var# gedit /etc/apache2/sites-enabled/000-default 
[/code]

Finally, restart Apache2.

[code]
root@debian /var/www $service apache2 restart
[/code]

[h3]Test installation[/h3]

[code]cd /var/www[/code]

create a php file to test the php installation[code]nano phpinfo.php[/code]

Insert into the file:
[code]
<?php
  phpinfo();
?>
[/code]
(save CTRL+0, ENTER, CTRL+X)

open webbrowser on PC and try #^[url=http://localhost/phpinfo.php]http://localhost/phpinfo.php[/url] (page shows infos on php)

connect phpMyAdmin with MySQL database [code]nano /etc/apache2/apache2.conf
[/code]
- CTRL+V... to the end of the file
- Insert at the end of the file:  (save CTRL+0, ENTER, CTRL+X)[code]Include /etc/phpmyadmin/apache.conf[/code]

restart apache
[code]/etc/init.d/apache2 restart
apt-get update
apt-get upgrade
reboot[/code]

[b]phpMyAdmin[/b]

open webbrowser on PC and try #^[url=http://localhost/phpmyadmin]http://localhost/phpmyadmin[/url]

(Source #^[url=http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#]http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#[/url])

[h3]Create an empty database... that is later used by the red#matrix[/h3]


open webbrowser on PC and try #^[url=http://localhost/phpmyadmin]http://localhost/phpmyadmin[/url]

Create an empty database, for example named "red".
Create a database user, for example "red".
Grant all rights for the user "red" to the database "red".

Note the access details (hostname, username, password, database name).


[h2]Fork the project on github[/h2]

Please follow the instruction in the offiical [url=http://git-scm.com/book/en/v2/GitHub-Contributing-to-a-Project] documentation[/url] of git.
It is a good idea to read the whole manual! Git is different to other version control systems in many ways.

Now you should
[list]
[*] create an account at github.com
[*] fork https://github.com/friendica/red
[*] fork https://github.com/friendica/red-addons
[/list]

If you not want to use GIT from the command line - there is a usefull Eclipse plugin named ""Eclipse Mylyn to GitHub connector".


[h2]Install RED and its Addons[/h2]

[h3]Git at your computer / vm[/h3]

You should have created an account on github and forked the projects befor you procceed.

Delete the directory www
[code]root@debian:/var# rm -R www/
[/code]

Install git (and optionally git-gui a client gui)
[code]apt-get install git git-gui[/code]

[h3]Download red#matri and addons[/h3]

Download the main project red and red-addons
[code]
root@debian:/var# git clone https://github.com/yourname/red www
root@debian:/var# cd www/
root@debian:/var/www# git clone https://github.com/yourname/red-addons addon
[/code]

Make this extra folder
[code]
root@debian:/var/www# mkdir -p "store/[data]/smarty3"
[/code]

Create .htconfig.php and make it writable by the webserver
[code]
root@debian:/var/www# touch .htconfig.php
root@debian:/var/www# chmod ou+w .htconfig.php
[/code]

Make user www-data (webserver) is the owner all the project files
[code]
root@debian:/var/www# cd ..
root@debian:/var# chown -R www-data:www-data www/
[/code]

Add yourself ("surfer" in this example) to the group www-data. Why? Later you want to modify files in eclipse or in another editor.
Then make all files writable by the group www-date you are now a member of.
[code]
root@debian:/var# cd www/
root@debian:/var/www# usermod -G www-data surfer
root@debian:/var# chmod -R  g+w www/
[/code]

Restart the computer (or vm)
If you are still not able to modify the project files you can check the members of the group www-data with
[code]
cat /etc/group
[/code]

[h3]Register yourself as admin[/h3]

Open http://localhost and init the matrix

Befor you register a first user switch off the registration mails.
Open /var/www/.htconfig.php
and make sure "0" is set in this line
[code]
$a->config['system']['verify_email'] = 0;
[/code]
You should be able to change the file as "yourself" (instead of using root or www-data).

[h3]Cron and the poller[/h3]

Important!
Run the poller  to pick up the recent "public" postings of your friends
Set up a cron job or scheduled task to run the poller once every 5-10
minutes to pick up the recent "public" postings of your friends

[code]
crontab -e
[/code]

Add
[code]
*/10 * * * * cd /var/www/; /usr/bin/php include/poller.php
[/code]

If you don't know the path to PHP type
[code]
whereis php
[/code]


[h2]Debug the server via eclipse[/h2]

[h3]Check the configuration of xdebug[/h3]

You shoud already have installed xdebug in the steps befor
[code]
apt-get install php5-xdebug
[/code]

Configuring Xdebug

Open your terminal and type as root (su -l)
[code]
gedit /etc/php5/mods-available/xdebug.ini
[/code]

if the file is empty try this location
[code]
gedit /etc/php5/conf.d/xdebug.ini
[/code]

That command should open the text editor gedit with the Xdebug configuration file
At the end of the file content append the following text

xdebug.remote_enable=on
xdebug.remote_handler=dbgp
xdebug.remote_host=localhost
xdebug.remote_port=9000

Save changes and close the editor.
In you terminal type to restart the web server.
[code]
service apache2 restart
[/code]


[h3]Install Eclipse and start debugging[/h3]

Install eclipse.
Start eclipse with default worspace (or as you like)

Install the PHP plugin
Menu > Help > Install new software...
Install "PHP Developnent Tools ..."

Optionally - Install the GitHub connector plugin
Menu > Help > Install new software...
Install "Eclipse Mylyn to GitHub connector"

Configure the PHP plugin
Menu > Window > Preferences...
> General > Webbrowser > Change to "Use external web browser"
> PHP > Debug > Debug Settings > PHP Debugger > Change to "XDebug"

Create a new PHP project
Menu > File > New Project > Choose PHP > "PHP Project"
> Choose Create project at existing location" and "/var/www"

Start debugging
Open index.php and "Debug as..."
Choose as Launch URL: "http://localhost/"

Expected:
[list]
[*] The web browser starts
[*] The debugger will stop at the first php line
[/list]


[h2]Contribute your changes via github[/h2]

[h3]Preparations[/h3]

There is a related page in this docs: [zrl=[baseurl]/help/git_for_non_developers]Git for Non-Developers[/zrl].
As stated befor it is recommended to read the official documentation [url=http://git-scm.com/book/en/v2/GitHub-Contributing-to-a-Project]GitHub-Contributing-to-a-Project[/url] of git.

Eclipse has a usefull plugin for GIT: "Eclipse Mylyn to GitHub connector".

Make sure you have set your data
[code]
surfer@debian:/var/www$ git config --global user.name "Your Name"
surfer@debian:/var/www$ git config --global user.email "your@mail.com"
[/code]

[h3]Your first contribution[/h3]

Create a descriptive topic branch
[code]
surfer@debian:/var/www$ git checkout -b dev_beginning
[/code]

Make sure your local repository is up-to-date with the main project.
Add the original repository as a remote named “upstream” if not done yet
[code]
surfer@debian:/var/www$ git remote add upstream https://github.com/friendica/red
[/code]

Fetch the newest work from that remote
[code]
surfer@debian:/var/www$ git fetch upstream
surfer@debian:/var/www$ git merge upstream/master
[/code]

Hint: You can list the branches
[code]
surfer@debian:/var/www$ git branch -v
[/code]

Make your changes. In this example it is a new doc file.

Check your modifications
[code]
surfer@debian:/var/www$ git status
[/code]

Add (stage) the new file
[code]
surfer@debian:/var/www$ git add doc/dev_beginner.bb
[/code]

Commit the changes to your local branch. This will open an editor to provide a message.
[code]
surfer@debian:/var/www$ git commit -a
[/code]

Push back up to the same topic branch online
[code]
surfer@debian:/var/www$ git push
[/code]

Now you can go to your (online) account at github and create the pull request.

[h3]Following contributions[/h3]

In case the main devolpers want you to change something.
Fetch the newest work from the remote upstream/master to be sure you have the latest changes.
[code]
surfer@debian:/var/www$ git fetch upstream
surfer@debian:/var/www$ git merge upstream/master
[/code]
Make your changes, test them, commit (to local repository), push (to online repository)
[code]
surfer@debian:/var/www$ git status
surfer@debian:/var/www$ git commit -a -m "added modification of branch"
surfer@debian:/var/www$ git push
[/code]


#include doc/macros/main_footer.bb;