[b]How to install the Red Matrix on a Raspberry Pi[/b]

[zrl=[baseurl]/help/main] Back to the main page[/zrl]
Last update 2014-02-22
[hr]

You just bought a Raspberry Pi and want to run the RED Matrix with your own domain name?

Then this page is for you! You will:
[list=1]
[*] Install Raspberry OS (Debian Linux) on a Raspberry
[*] Install Apache Web Server, PHP, MaySQL, phpMyAdmin
[*] Register a free domain (dynamic DNS) and use it for your RED hub
[*] Install the RED Matrix
[*] Keep your Raspberry Pi and your Redmatrix up-to-date
[*] TODO Running Friendica with SSL
[*] TODO Make the webserver less vulnarable to attacks
[/list]

[size=large]1. Install Raspberry OS (Debian Linux)[/size]

instructions under #^[url=http://www.raspberrypi.org/downloads]http://www.raspberrypi.org/downloads[/url]
This page links to the quick start containing detailed instruction.

[b]Format SD card[/b]

using the programm gparted under Linux Mint 15

format as FAT32

[b]Download NOOBS (offline and network install)[/b]

#^[url=http://downloads.raspberrypi.org/noobs]http://downloads.raspberrypi.org/noobs[/url]

unzip

copy unzipped files to SD card

[b]Install Raspbian as OS on the Rasperry Pi[/b]

connect with keyboard via USB

connect with monitor via HDMI

Insert SD card into Rasperry Pi

Connect with power supply to switch on the Rasperry

choose Raspbian as OS (&gt; installs Raspbian....)

wait for the coniguration program raspi-config (you can later start it by sudo raspi-config)

[b]Configure Raspbian[/b]

in raspi-config &gt; advanced &gt; choose to use ssh (!! You need this to connect to administrate your Pi from your PC !!)

in raspi-config &gt; change the password (of default user &quot;pi&quot; from &quot;raspberry&quot; to your password)

in raspi-config (optional) &gt; Internationalisation options &gt; Change Locale &gt; to de_DE.utf-8 utf-8 (for example)

in raspi-config (optional) &gt; Internationalisation options &gt; Change Timezoe &gt; set your timezone

in raspi-config (optional) &gt; Overlock &gt; medium

(Source #^[url=http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#]http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#[/url])


[b]More[/b]

[code]sudo reboot[/code]

Now its time to connect the Pi to the network.
[ul]
[*] pull out keyboard
[*] pull out monitor
[*] you even can pull out the power supply (USB)
[*] plug-in the network cable to the router
[*] plug-in the power supply again
[*] wait for a minute or to give the Pi time to boot and start ssh...
[/ul]

On your PC connect to the Pi to administrate (here update it).
Open the console on the PC (Window: Start &gt; cmd, Linux: Shell)

Hint: use the router admin tool to find out the IP of your PI[code]ssh pi@192.168.178.37
sudo apt-get update
sudo apt-get dist-upgrade[/code]

(Source #^[url=http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#]http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#[/url])



[size=large]2. Install Apache Web Server, PHP, MaySQL, phpMyAdmin[/size]

[b]Install Apache Webserver[/b]

[code]sudo bash
sudo groupadd www-data[/code] might exist already

[code]sudo usermod -a -G www-data www-data
sudo apt-get update
sudo reboot[/code]

wait...
reconnect via ssh, example: [code]ssh pi@192.168.178.37
sudo apt-get install apache2 apache2-doc apache2-utils[/code]

Open webbrowser on PC and check #^[url=http://192.168.178.37]http://192.168.178.37[/url]
Should show you a page like &quot;It works&quot;

(Source #^[url=http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#]http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#[/url])


[b]Install PHP, MaySQL, phpMyAdmin[/b]

[code]sudo bash
apt-get install libapache2-mod-php5 php5 php-pear php5-xcache php5-curl
apt-get install php5-mysql
apt-get install mysql-server mysql-client[/code] enter and note the mysql passwort

[code]apt-get install phpmyadmin[/code]

Configuring phpmyadmin
- Select apache2
- Configure database for phpmyadmin with dbconfig-common?: Choose Yes

(Source #^[url=http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#]http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#[/url])


[b]Test installation[/b]

[code]cd /var/www[/code]

create a php file to test the php installation[code]sudo nano phpinfo.php[/code]

Insert into the file:[code]
&lt;?php
  phpinfo();
?&gt;
[/code]
(save CTRL+0, ENTER, CTRL+X)

open webbrowser on PC and try #^[url=http://192.168.178.37/phpinfo.php]http://192.168.178.37/phpinfo.php[/url] (page shows infos on php)

connect phpMyAdmin with MySQL database [code]nano /etc/apache2/apache2.conf[/code]
- CTRL+V... to the end of the file
- Insert at the end of the file:  (save CTRL+0, ENTER, CTRL+X)[code]Include /etc/phpmyadmin/apache.conf[/code]

restart apache[code]/etc/init.d/apache2 restart
sudo apt-get update
sudo apt-get upgrade
sudo reboot[/code]

(Source #^[url=http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#]http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#[/url])


[b]phpMyAdmin[/b]

open webbrowser on PC and try #^[url=http://192.168.178.37/phpmyadmin]http://192.168.178.37/phpmyadmin[/url]

(Source #^[url=http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#]http://www.manfred-steger.de/tuts/20-der-eigene-webserver-mit-dem-raspberry-pi#[/url])


[b]Create an empty database... that is later used by RED[/b]

open webbrowser on PC and try #^[url=http://192.168.178.37/phpmyadmin]http://192.168.178.37/phpmyadmin[/url]

Create an empty database

Note the access details (hostname, username, password, database name).


[size=large]3. Selfhost[/size] 

(Source: #^[url=http://www.techjawab.com/2013/06/setup-dynamic-dns-dyndns-for-free-on.html]http://www.techjawab.com/2013/06/setup-dynamic-dns-dyndns-for-free-on.html[/url])

#^[url=http://freedns.afraid.org/signup/]http://freedns.afraid.org/signup/[/url]

[b]Step 1[/b]
Register for a Free domain at #^[url=http://freedns.afraid.org/signup/]http://freedns.afraid.org/signup/[/url]
(We will take techhome.homenet.org in this guide)

[b]Step 2[/b]

Logon to FreeDNS (where you just registered) and goto #^[url=http://freedns.afraid.org/dynamic/]http://freedns.afraid.org/dynamic/[/url]
Right click on &quot;Direct Link&quot; and copy the URL and paste it somewhere.
You should notice a large and unique alpha-numeric key in the URL, make a note of it as shown below:
[code]http://freedns.afraid.org/dynamic/update.php?alphanumeric-key[/code]


[b]Step 3[/b]
Install inadyn using the following command:[code]sudo apt-get install inadyn[/code]

[b]Step 4[/b]
Configure inadyn using the below steps:[code]sudo nano /etc/inadyn.conf[/code]
And add the following contains in it replacing the actual values:
[code]
--username [color=red]techhome[/color]
--password [color=red]mypassword[/color]
--update_period 3600
--forced_update_period 14400
--alias [color=red]techhome.homenet.org&lt;/b&gt;,[color=red]alphanumeric key[/color]
--background
--dyndns_system default@freedns.afraid.org
--syslog
[/code]


[b]Step 5[/b]

Now, we need to ensure that the DNS updater (Inadyn) runs automatically after every re-boot[code]export EDITOR=gedit &amp;&amp; sudo crontab -e[/code]
Add the following line:[code]@reboot /usr/sbin/inadyn[/code]


[b]Step 6[/b]

Reboot system and then run the following command to ensure inadyn is running:[code]
sudo reboot
ps -A | grep inadyn
[/code]
Now your host is ready and up for accessing from internet...
You can trying ssh-ing from another computer over the internet
[code]ssh username@techhome.homenet.org[/code]
Or, if any web server is running, then simply browse to  #^[url=http://techhome.homenet.org]http://techhome.homenet.org[/url]
Or, you can just ping it to test ping techhome.homenet.org
To check the logs you can use this:
[code]more /var/log/messages |grep INADYN[/code]


[size=large]4. Install RED [/size] 

(Source: #^[zrl=https://friendicared.net/help/Install]https://friendicared.net/help/Install[/zrl])

Linux Appache document root is /var/www/
Two files exist there (created by the steps above): index.html, phpinfo.php


[b]Install RED and its Addons[/b]

Cleanup: Remove the directory www/ (Git will not create files and folders in directories that are not empty.) Make sure you are in directory var[code]pi@pi /var $ cd /var[/code]
 
Remove directory[code]pi@pi /var $ sudo rm -rf www/[/code]

Download the sources of RED from GIT
[code]pi@pi /var $ sudo git clone https://github.com/friendica/red.git www[/code]

Download the sources of the addons from GIT
[code]pi@pi /var/www $ sudo git clone https://github.com/friendica/red-addons.git addon[/code]

Make user www-data the owner of the whole red directory (including subdirectories and files)
(TODO: This step has to be proofed by the next installation.)
[code]pi@pi /var $ chown -R www-data:www-data /var/www/[/code]

Check if you can update the sources from git[code]
pi@pi /var $ cd www
pi@pi /var/www $ git pull
[/code]

Check if you can update the addons
[code]pi@pi /var/www $ cd addon/
pi@pi /var/www/addon $ sudo git pull[/code]

Make sure folder view/tpl/smarty3 exists and is writable by the webserver
[code]pi@pi /var/www $ sudo chmod ou+w view/tpl/smarty3/[/code]

Create .htconfig.php and is writable by the webserver
[code]pi@pi /var/www $ sudo touch .htconfig.php
pi@pi /var/www $ sudo chmod ou+w .htconfig.php[/code]

Prevent search engines from indexing your site. Why? This can fill up your database.
(Source: [url=http://wiki.pixelbits.de/redmatrix]Pixelbits[/url] )
[code]pi@pi /var/www $ sudo touch robots.txt[/code]
Open the file.
[code]pi@pi /var/www $ sudo nano robots.txt[/code]
Paste this text and save.
[code]
# Prevent search engines to index this site
  User-agent: *
  Disallow: /search
[/code]


[b]First start and initial configuration of your RED Matrix hub[/b]

In browser open #^[zrl=http://einervonvielen.mooo.com/]http://einervonvielen.mooo.com/[/zrl]
(Replace einervonvielen.mooo.com by your domain, see chapter selfhost. Be patient. It takes time.)
(#^[zrl=http://einervonvielen.mooo.com/index.php?q=setup]http://einervonvielen.mooo.com/index.php?q=setup[/zrl])

There might be errors like the following.

Error: libCURL PHP module required but not installed.
Solution:
apt-get install php5-curl

Error: Apache webserver mod-rewrite module is required but not installed.
Solution
(Source: #^[url=http://xmodulo.com/2013/01/how-to-enable-mod_rewrite-in-apache2-on-debian-ubuntu.html]http://xmodulo.com/2013/01/how-to-enable-mod_rewrite-in-apache2-on-debian-ubuntu.html[/url])
The default installation of Apache2 comes with mod_rewrite installed. To check whether this is the case, verify the existence of /etc/apache2/mods-available/rewrite.load
- pi@pi /var/www $ nano /etc/apache2/mods-available/rewrite.load
 (You should find the contendt: LoadModule rewrite_module /usr/lib/apache2/modules/mod_rewrite.so)
To enable and load mod_rewrite, do the rest of steps.
Create a symbolic link in /etc/apache2/mods-enabled
- pi@pi /var/www $ sudo a2enmod rewrite
Then open up the following file, and replace every occurrence of &quot;AllowOverride None&quot; with &quot;AllowOverride all&quot;.
- pi@pi /var/www $ sudo nano /etc/apache2/sites-available/default
Finally, restart Apache2.
- pi@pi /var/www $ sudo service apache2 restart

Error store is writable (not checked)
Solution:
(TODO: Make writeable to group www-data only?)
pi@pi /var/www $ sudo mkdir store
pi@pi /var/www $ chown -R www-data:www-data /var/www/red/
pi@pi /var/www $ sudo chmod ou+w view

[b]More[/b]

Set up a cron job to run the poller once every 15 minutes in order to perform background processing.
- pi@pi /var/www $ which php
Make sure you are in the document root directory of the webserver
- pi@pi /var/www $ cd /var/www/
Try to execute the poller in oder to make sure it works
- pi@pi /var/www $ /usr/bin/php include/poller.php
Create the cronjob
- pi@pi /var/www $ crontab -e
Enter
- */15 * * * * cd /var/www/; /usr/bin/php include/poller.php
- Save and exit.


[size=large]5. Keep your Raspberry Pi and your Redmatrix up-to-date[/size] 

Git update of RED every day at 4 am and addons at 5 am every day
Try if the command is working
- pi@pi /var/www $ sudo git pull
Create the cronjob
- pi@pi /var/www $ crontab -e
Enter the following to update RED at 4:01 am every day
- 01 04 * * * cd /var/www/; sudo git pull
Enter the following to update the addons at 5:01 am every day
- 01 05 * * * cd /var/www/addon/; sudo git pull
Enter the following to update the Raspberry Pi (Raspbian OS = Debian) at 6:01 am every day
- 01 06 * * * sudo aptitude -y update &amp;&amp; sudo aptitude -y safe-upgrade
Save and exit.

[size=large]6. Running Friendica with SSL[/size] 

Follow the instructions here:
#^[url=https://github.com/friendica/friendica/wiki/Running-Friendica-with-SSL]https://github.com/friendica/friendica/wiki/Running-Friendica-with-SSL[/url]