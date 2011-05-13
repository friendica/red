Friendika uses plugins to provide connectivity to some networks, such as Facebook and Twitter. There is also a plugin to post through to an existing account on a Status.Net service. You do not require this to communicate with Status.Net members from Friendika - only if you wish to post to an already existing account.

All three of these plugins require an account on the target network. In addition you (or typically the server administrator) will need to obtain an API key to provide authenticated access to your Friendika server.

The details of configuring each service follows (this information comes directly from the plugin source files):

**Twitter Plugin for Friendika**

* Author: Tobias Diekershoff
* tobias.diekershoff@gmx.net

* License:3-clause BSD license (same as Friendika)

Configuration:
To use this plugin you need a OAuth Consumer key pair (key & secret)
you can get it from Twitter at https://twitter.com/apps

Register your Friendika site as "Client" application with "Read & Write" access
we do not need "Twitter as login". When you've registered the app you get the
OAuth Consumer key and secret pair for your application/site.

Add this key pair to your global .htconfig.php
```
$a->config['twitter']['consumerkey'] = 'your consumer_key here';
$a->config['twitter']['consumersecret'] = 'your consumer_secret here';
```

To activate the plugin itself add it to the 


```
$a->config['system']['addon']
```
setting. After this, your user can configure their Twitter account settings
from "Settings -> Plugin Settings".

Requirements: PHP5, curl [Slinky library]

Documentation: http://diekershoff.homeunix.net/redmine/wiki/friendikaplugin/Twitter_Plugin


**StatusNet Plugin for Friendika**

* Author: Tobias Diekershoff
* tobias.diekershoff@gmx.net

* License:3-clause BSD license (same as Friendika)

* Configuration:

Global Configuration
To activate this addon add statusnet to the list of active addons in your .htconfig.php file $a->config['system']['addon'] = "statusnet, ...".
User Configuration

When the addon is activated the user has to aquire three things in order to connect to the StatusNet account of choice.

the base URL for the StatusNet API, for identi.ca this is https://identi.ca/api/


* OAuth Consumer key & secret

To get the OAuth Consumer key pair the user has to (a) ask her Friendika admin if a pair already exists or (b) has to register the Friendika server as a client application on the StatusNet server. This can be done from the account settings under "Connect -> Connections -> Register an OAuth client application -> Register new application".

During the registration of the OAuth client remember the following:

* there is no callback url
* register a desktop client
* with read & write access
* the Source URL should be the URL of your Friendika server

After the required credentials for the application are stored in the configuration you have to actually connect your Friendika account with StatusNet. To do so follow the Sign in with StatusNet button, allow the access and copy the security code into the addon configuration. Friendika will then try to acquire the final OAuth credentials from the API, if successful the addon settings will allow you to select to post your public messages to your StatusNet account (have a look behind the little lock symbol).

To activate the plugin itself add it to the $a->config['system']['addon']
setting. After this, your user can configure their Status.Net account settings
from "Settings -> Plugin Settings".

Documentation: http://diekershoff.homeunix.net/redmine/wiki/friendikaplugin/StatusNet_Plugin



**Installing the Friendika/Facebook connector**

* register an API key for your site from developer.facebook.com


a. We'd be very happy if you include "Friendika" in the application name
to increase name recognition. The Friendika icons are also present
in the images directory and may be uploaded as a Facebook app icon.
Use images/friendika-16.jpg for the Icon and images/friendika-128.jpg for the Logo.

b. The url should be your site URL with a trailing slash.
You may use http://portal.friendika.com/privacy as the privacy policy
URL unless your site has different requirements, and
http://portal.friendika.com as the Terms of Service URL unless
you have different requirements. (Friendika is a software application
and does not require Terms of Service, though your installation of it might).

c. Set the following values in your .htconfig.php file

```
$a->config['facebook']['appid'] = 'xxxxxxxxxxx';
$a->config['facebook']['appsecret'] = 'xxxxxxxxxxxxxxx';
```
Replace with the settings Facebook gives you.

2. Enable the facebook plugin by including it in .htconfig.php - e.g.

```
$a->config['system']['addon'] = 'plugin1,plugin2,facebook';
```

3. Visit the Facebook Settings section of the "Settings->Plugin Settings" page.
and click 'Install Facebook Connector'.

4. This will ask you to login to Facebook and grant permission to the
plugin to do its stuff. Allow it to do so.

5. You're done. To turn it off visit the Plugin Settings page again and
'Remove Facebook posting'.

Videos and embeds will not be posted if there is no other content. Links
and images will be converted to a format suitable for the Facebook API and
long posts truncated - with a link to view the full post.

Facebook contacts will not be able to view private photos, as they are not able to
authenticate to your site to establish identity. We will address this
in a future release.




 


