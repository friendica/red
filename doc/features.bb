[b]Features[/b]

[b][size=24]Red Matrix Features[/size][/b]


The Red Matrix is a general-purpose communication network, with several unique features.  It is designed to be used by the widest range of users on the web, from non-technical bloggers, to expert PHP programmers and seasoned systems administrators.

This page lists some of the core features of Red that are bundled with the official release.  As with most free and open source software, there may be many other extensions, additions, plugins, themes and configurations that are limited only by the needs and imagination of Red's users.

[b][size=20]Built for Privacy and Freedom[/size][/b]

One of the design goals of Red is to enable easy communication on the web, while preserving privacy, if so desired by users.  To achieve this goal, Red includes a number of features allowing arbitrary levels of privacy:

[b]Affinity Slider[/b]

When adding contacts in the Red Matrix, users have the option of assigning affinity levels to the new member in their contact list.  For example, when adding someone who happens to be a person who's blog you follow, you could assign their channel an affinity level of &quot;Acquaintances&quot;. 

[img]https://friendicared.net/photo/b07b0262e3146325508b81a9d1ae4a1e-0.png[/img]

On the other hand, when adding a friend's channel, they could be placed under the affinity level of &quot;Friends&quot;.

At this point, Red's [i]Affinity Slider[/i] tool, which usually appears at the top of your &quot;Matrix&quot; page, allows content on your Red account to be displayed by desired affinity levels. By moving the slider to cover all contacts with affinity levels of &quot;Me&quot; to &quot;Friends&quot;, only contacts (or channels) that are marked as &quot;Me&quot;, &quot;Best Friends&quot;, and &quot;Friends&quot; will be displayed on your page.  All other channels and contacts, such as the contact added under affinity level &quot;Acquaintances&quot;, will not be displayed.

The Affinity Slider allows instantaneous filtering of large amounts of content, grouped by levels of closeness.

[b]Access Control Lists[/b]

When sharing content with someone in their contact list, users have the option of restricting who sees the content.  By clicking on the padlock underneath the sharing box, one may choose desired recipients of the post, by clicking on their names.

Once sent, the message will be viewable only by the sender and the selected recipients.  In other words, the message will not appear on any public walls.

Access Control Lists may be applied to content and posts, photos, events, webpages, chatrooms and files. 

[b]Single Sign-on[/b]

Access Control Lists work for all channels in the matrix due to our unique single sign-on technology. Most internal links provide an identity token which can be verified on other Redmatrix sites and used to control access to private resources. You login once to your home hub. After that, authentication to all Redmatrix resources is "magic".


[b]WebDAV enabled File Storage[/b]

Files may be uploaded to your personal storage area using your operating system utilities (drag and drop in most cases). You may protect these files with Access Control Lists to any combination of Redmatrix members (including some third party network members) or make them public.

[b]Photo Albums[/b]

Store photos in albums. These may be protected by Access Control Lists.

[b]Events Calendar[/b]

Create and manage events, which may also be protected with Access Control Lists. Events can be exported to other software using the industry standard vcalendar/iCal format and shared in posts with others. Birthday events are automatically added from your friends and converted to your correct timezone so that you will know precisely when the birthday occurs - no matter where you are located in the world in relation to the birthday person. 

[b]Chatrooms[/b]

You may create any number of personal chatrooms and allow access via Access Control Lists. These are typically more secure than XMPP, IRC, and other Instant Messaging transports, though we also allow using these other services via plugins.       

[b]Webpage Building[/b]

Redmatrix has many "Content Management" creation tools for building webpages, including layout editing, menus, blocks, widgets, and page/content regions. All of these may be access controlled so that the resulting pages are private to their intended audience. 

[b]Apps[/b]

Apps may be built and distributed by members. These are different from traditional "vendor lockin" apps because they are controlled completely by the author - who can provide access control on the destination app pages and charge accordingly for this access. Most apps in Redmatrix are free and can be created easily by those with no programming skills. 

[b]Layout[/b]

Page layout is based on a description language called Comanche. Redmatrix is itself written in Comanche layouts which you can change. This allows a level of customisation you won't typically find in so-called "multi-user environments".

[b]Bookmarks[/b]

Share and save/manage bookmarks from links provided in conversations.    
 
 
[b]Private Message Encryption and Privacy Concerns[/b]

Messages marked [b]private[/b] are encrypted with AES-CBC 256-bit symmetric cipher, which is then protected (encrypted in turn) by public key cryptography, based on 4096-bit RSA keys, associated with the channel that is sending the message.  

These private messages are also stored in an encrypted form on remote systems. 

Each Red channel has it's own unique set of private and associated public RSA 4096-bit keys, generated when the channels is first created.

Additionally, messages may be created utilising "end-to-end encryption" which cannot be read by Redmatrix operators or ISPs or anybody who does not know the passcode. 

Public messages are generally not encrypted in transit or in storage.  

Private messages may be retracted (unsent) although there is no guarantee the recipient hasn't read it yet.

Posts and messages may be created with an expiration date, at which time they will be deleted/removed on the recipient's site.  


[b]Service Federation[/b]

In addition to addon "cross-post connectors" to a variety of alternate networks, there is native support for importation of content from RSS/Atom feeds and using this to create special channels. Also, an experimental but working implementation of the Diaspora protocol allows communication with people on the Friendica and Diaspora decentralised social networks. This is currently marked experimental because these networks do not have the same level of privacy and encryption features and abilities as Redmatrix and may present privacy risks.

There is also experimental support for OpenID authentication which may be used in Access Control Lists. This is a work in progress. 

Channels may have permissions to become "derivative channels" where two or more existing channels combine to create a new topical channel. 

[b]Collections[/b]

"Collections" is our implementation of privacy groups, which is similar to Google "Circles" and Diaspora "Aspects". This allows you to filter your incoming stream by collections or groups, and automatically set the outbound Access Control List to only those in the Collection when you post. You may over-ride this at any time. 


[b]Directory Services[/b]

We provide easy access to a directory of members and provide decentralised tools capable of providing friend "suggestions". The directories are normal Redmatrix sites which have chosen to accept the directory server role. This requires more resources than most typical sites so is not the default. Directories are synchronised and mirrored so that they all contain up-to-date information on the entire network (subject to normal propagation delays).  
 

[b]TLS/SSL[/b]

For Red hubs that use TLS/SSL, client to server communications are encrypted via TLS/SSL.  Given recent disclosures in the media regarding widespread, global surveillance and encryption circumvention by the NSA and GCHQ, it is reasonable to assume that HTTPS-protected communications may be compromised in various ways.

[b]Channel Settings[/b]

When a channel is created, a role is chosen which applies a number of pre-configured security and privacy settings. These are chosen for best practives to maintain privacy at the requested levels.  

If you choose a "custom" privacy role, each channel allows fine-grained permissions to be set for various aspects of communication.  For example, under the &quot;Security and Privacy Settings&quot; heading, each aspect on the left side of the page, has six (6) possible viewing/access options, that can be selected by clicking on the dropdown menu. There are also a number of other privacy settings you may edit.  

[img]https://friendicared.net/photo/0f5be8da282858edd645b0a1a6626491.png[/img]

The options are:

 - Nobody except yourself.
 - Only those you specifically allow.
 - Anybody in your address book.
 - Anybody on this website.
 - Anybody in this network.
 - Anybody authenticated.
 - Anybody on the Internet.


[b]Public and Private Forums[/b]

Forums are typically channels which may be open to participation from multiple authors. There are currently two mechanisms to post to forums: 1) "wall-to-wall" posts and 2) via forum @mention tags. Forums can be created by anybody and used for any purpose. The directory contains an option to search for public forums. Private forums can only be posted to and often only seen by members.


[b]Account Cloning[/b]

Accounts in the Red Matrix are referred to as [i]nomadic identities[/i], because a user's identity is not bound to the hub where the identity was originally created (see What is Zot? for the full explanation).  For example, when you create a Facebook or Gmail account, it is tied to those services.  They cannot function without Facebook.com or Gmail.com.  

By contrast, say you've created a Red identity called [b]tina@redhub.com[/b].  You can clone it to another Red hub by choosing the same, or a different name: [b]liveForever@SomeRedMatrixHub.info[/b]

Both channels are now synchronized, which means all your contacts and preferences will be duplicated on your clone.  It doesn't matter whether you send a post from your original hub, or the new hub.  Posts will be mirrored on both accounts.

This is a rather revolutionary feature, if we consider some scenarios:

 - What happens if the hub where an identity is based suddenly goes offline?  Without cloning, a user will not be able to communicate until that hub comes back online.  With cloning, you just log into your cloned account, and life goes on happily ever after.

 - The administrator of your hub can no longer afford to pay for his free and public Red Matrix hub. He announces that the hub will be shutting down in two weeks.  This gives you ample time to clone your identity(ies) and preserve your Red relationships, friends and content.

 - What if your identity is subject to government censorship?  Your hub provider is compelled to delete your account, along with any identities and associated data.  With cloning, the Red Matrix offers [b]censorship resistance[/b].  You can have hundreds of clones, if you wanted to, all named different, and existing on many different hubs, strewn around the internet.  

Red offers interesting new possibilities for privacy. You can read more at the &lt;&lt;Private Communications Best Practices&gt;&gt; page.

Some caveats apply. For a full explanation of identity cloning, read the &lt;HOW TO CLONE MY IDENTITY&gt;.

[b]Multiple Profiles[/b]

Any number of profiles may be created containing different information and these may be made visible to certain of your connections/friends. A "default" profile can be seen by anybody and may contain limited information, with more information available to select groups or people. 

[b]Account Backup[/b]

Red offers a simple, one-click account backup, where you can download a complete backup of your profile(s).  

Backups can then be used to clone or restore a profile.

[b]Account Deletion[/b]

Accounts can be immediately deleted by clicking on a link. That's it.  All associated content is then deleted from the matrix (this includes posts and any other content produced by the deleted profile). Depending on the number of connections you have, the process of deleting remote content could take some time but it is scheduled to happen as quickly as is practical.

[b][size=20]Content Creation[/size][/b]

[b]Writing Posts[/b]

Red supports a number of different ways of adding rich-text content. The default is a custom variant of BBcode, tailored for use in Redmatrix. You may also enable the use of Markdown if you find that easier to work with. A visual editor may also be used. The traditional visual editor for Redmatrix had some serious issues and has since been removed. We are currently looking for a replacement. 

When creating &quot;Websites&quot;, content may be entered in HTML, Markdown, BBcode, and/or plain text.

[b]Deletion of content[/b]
Any content created in the Red Matrix remains under the control of the member (or channel) that originally created it.  At any time, a member can delete a message, or a range of messages.  The deletion process ensures that the content is deleted, regardless of whether it was posted on a channel's primary (home) hub, or on another hub, where the channel was remotely authenticated via Zot.

[b]Media[/b]
Similar to any other modern blogging system, social network, or a micro-blogging service, Red supports the uploading of files, embedding of videos, linking web pages.

[b]Previewing/Editing[/b] 
Post can be previewed prior to sending and edited after sending.

[b]Extending Redmatrix[/b]

Redmatrix can be extended in a number of ways, through site customisation, personal customisation, option setting, themes, and addons/plugins. 

[b]API[/b]

An API is available for use by third-party services. This is based originally on the early Twitter API (for which hundreds of third-party tools exist). It is currently being extended to provide access to facilities and abilities which are specific to Redmatrix. Access may be provided by login/password or OAuth and client registration of OAuth applications is provided.

 

#include doc/macros/main_footer.bb;
