[b]How to cross-post to a GNUsocial instance[/b]

Start on the GNUSocial instance where you have your account.

In the GNUSocial instance, go to Settings > Connections. In the right column under "Developers," click the link to "Register an OAuth client application to use with this instance of StatusNet." This link may be found at your instance here:

https://yourgnusocialinstance.org/settings/oauthapps

Next, click the link to Register a new application. That brings up the new application form. Here's what to do on each field.

Icon. I uploaded the RedMatrix icon located at this link, after saving it to my computer:

https://github.com/friendica/red/blob/master/images/rm-32.png

Name. Give the application an appropriate name. I called mine redmatrix. You might prefer r2g.

Description. Use this field to describe the purpose of the application. I put something to the effect of use for crossposting from RedMatrix to GNUsocial.

Source URL. Put the main domain name of the Red site you're using. Don't forget to put the "s" in https://yourredmatrixsite.com. If your Red installation is a subdomain, that would probably be called for.

Organization. Since RedMatrix is unorganized, I put that. If you use your installation for a group or business, that might be a good option.

Homepage. If your group is using a subdomain, you probably want to put your main domain URI here. Since I'm on a hosted site, I put redmatrix.me.

Callback URL. Leave blank.

Type of application: select "desktop."

Default access: select "Read-write."

All fields except the callback URL must be filled in.

Click on the save button.

Then click on the icon or the name of the application for the information you'll need to insert over on RedMatrix.

*****

Now open up a new tab or window and go to your RedMatrix account, to Settings > Feature settings. Find the StatusNet Posting Settings.

Insert the strings of numbers given on the GNUsocial site into the RedMatrix fields for Consumer Key and Consumer Secret.

The Base API Path (remember the trailing /) will be your instance domain, plus the /api/ following. It will probably look like this:

https://yourgnusocialinstance.org/api/

In case of doubt check on your GNUsocial instance site in order to find the domain URLs of the Request token, Access token, and Authorization. It will be the first part of the domains, minus the /oauth/....

StatusNet application name: Insert the name you gave to the application over on the GNUsocial site.

Click Submit.

A button will appear for you to "Sign in to StatusNet." Click it and that will open a tab or window on the GNUsocial site for you to click "Allow." Once clicked and successfully authorized, a security code number will appear. Copy it and go back to the RedMatrix app you just left and insert it in the field: "Copy the security code from StatusNet here." Click Submit.

If successful, your information from the GNUsocial instance should appear in the RedMatrix app.

You now have several options to choose, if you desire, and those will need to be confirmed by clicking "Submit" also. The most interesting is "Send public postings to StatusNet by default." This option automatically sends any post of yours made in your RedMatrix account to your GNUsocial instance.

If you don't choose this option, you will have an option to send a post to your GNUsocial instance by first opening the post (by clicking in the post text area) and clicking on the lock icon next to the Share button. Select the GNUsocial icon made up of three colored dialog baloons. Close that window, then make your post.

If all goes well, you have just cross-posted your RedMatrix post to your account on a GNUsocial instance.

Return to the [zrl=[baseurl]/help/main]Main documentation page[/zrl]

