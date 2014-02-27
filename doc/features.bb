[b]Features[/b]

[b][color= grey][size=24]Red Matrix Features[/size][/color][/b]


The Red Matrix is a general-purpose communication network, with several unique features.  It is designed to be used by the widest range of users on the web, from non-technical bloggers, to expert PHP programmers and seasoned systems administrators.

This page lists some of the core features of Red that are bundled with the official.  As with any free and open source software, there may be many other extensions, additions, plugins, themes and configurations that are limited only by the needs and imagination of Red's users.

[b][color= grey][size=20]Built for Privacy and Freedom[/size][/color][/b]

One of the design goals of Red is to enable easy communication on the web, while preserving privacy, if so desired by users.  To achieve this goal, Red includes a number of features allowing arbitrary levels of privacy:

[b][color= grey]Affinity Slider[/color][/b]

When adding contacts in the Red Matrix, users have the option of assigning affinity levels to the new member in their contact list.  For example, when adding someone who happens to be a person who's blog you follow, you could assign their channel an affinity level of &quot;Acquaintances&quot;. 

[img]https://friendicared.net/photo/b07b0262e3146325508b81a9d1ae4a1e-0.png[/img]

On the other hand, when adding a friend's channel, they could be placed under the affinity level of &quot;Friends&quot;.

At this point, Red's [i]Affinity Slider[/i] tool, which usually appears at the top of your &quot;Matrix&quot; page, allows content on your Red account to be displayed by desired affinity levels. By moving the slider to cover all contacts with affinity levels of &quot;Me&quot; to &quot;Friends&quot;, only contacts (or channels) that are marked as &quot;Me&quot;, &quot;Best Friends&quot;, and &quot;Friends&quot; will be displayed on your page.  All other channels and contacts, such as the contact added under affinity level &quot;Acquaintances&quot;, will not be displayed.

The Affinity Slider allows instantaneous filtering of large amounts of content, grouped by levels of closeness.

[b][color= grey]Access Control Lists[/color][/b]

When sharing content with someone in their contact list, users have the option of restricting who sees the content.  By clicking on the padlock underneath the sharing box, one could choose desired recipients of the post, by clicking on their names.

Once sent, the message will be viewable only by the sender and the selected recipients.  In other words, the message will not appear on any public walls.


[b][color=grey]Private Message Encryption and Privacy Concerns[/color][/b]

In the Red Matrix, public messages are not encrypted prior to leaving the originating server, they are also stored in the database in clear text.

Messages marked [b][color=white]private[/color][/b], however, are encrypted with AES-CBC 256-bit symmetric cipher, which is then protected (encrypted in turn) by public key cryptography, based on 4096-bit RSA keys, associated with the channel that is sending the message.  

Each Red channel has it's own unique set of private and associated public RSA 4096-bit keys, generated when the channels is first created.  

[b][color= grey]TLS/SSL[/color][/b]

For Red hubs that use TLS/SSL, client to server communications are encrypted via TLS/SSL.  Given recent disclosures in the media regarding widespread, global surveillance and encryption circumvention by the NSA and GCHQ, it is reasonable to assume that HTTPS-protected communications may be compromised in various ways.

[b][color= grey]Channel Settings[/color][/b]

In Red, each channel allows fine-grained permissions to be set for various aspects of communication.  For example, under the &quot;Security and Privacy Settings&quot; heading, each aspect on the left side of the page, has six (6) possible viewing/access options, that can be selected by clicking on the dropdown menu.

[img]https://friendicared.net/photo/0f5be8da282858edd645b0a1a6626491.png[/img]

The six options are:

 - Nobody except yourself.
 - Only those you specifically allow.
 - Anybody in your address book.
 - Anybody on this website.
 - Anybody in this network.
 - Anybody on the Internet.


[b][color= grey]Account Cloning[/color][/b]

Accounts in the Red Matrix are called to as [i]nomadic identities[/].  Nomadic, because a user's identity (see What is Zot? for the full explanation) is stuck to the hub where the identity was originally created.  For example, when you created your Facebook, or Gmail account, it is tied to those services.  They cannot function without Facebook.com or Gmail.com.  

By contrast, say you've created a Red identity called [b][color=white]tina@redhub.com[/color][/b].  You can clone it to another Red hub by choosing the same, or a different name: [b][color=white]liveForever@SomeRedMatrixHub.info[/color][/b]

Both channels are now synchronized, which means all your contacts and preferences will be duplicated on your clone.  It doesn't matter whether you send a post from your original hub, or the new hub.  Posts will be mirrored on both accounts.

This is a rather revolutionary feature, if we consider some scenarios:

 - What happens if the hub where an identity is based, suddenly goes offline?  Without cloning, a user will not be able to communicate until that hub comes back online.  With cloning, you just log into your cloned account, and life goes on happily ever after.

 - The administrator of your hub can no longer afford to pay for his free and public Red Matrix hub. He announces that the hub will be shutting down in two weeks.  This gives you ample time to clone your identity(ies) and preserve your Red relationships, friends and content.

 - What if your identity is subject to government censorship?  Your hub provider is compelled to delete your account, along with any identities and associated data.  With cloning, the Red Matrix offers [b][color=white]censorship resistance [/color][/b].  You can have hundreds of clones, if you wanted to, all named different, and existing on many different hubs, strewn around the internet.  

Red offers interesting new possibilities for privacy. You can read more at the &lt;&lt;Private Communications Best Practices&gt;&gt; page.

Some caveats apply. For a full explanation of identity cloning, read the &lt;HOW TO CLONE MY IDENTITY&gt;.


[b][color= grey]Account Backup[/color][/b]

Red offers a simple, one-click account backup, where you can download a complete backup of your profile(s).  

Backups can then be used to clone or restore a profile.

[b][color= grey]Account Deletion[/color][/b]

Accounts can be immediately deleted by clicking on a link. That's it.  All associated content is immediately deleted from the matrix (this includes posts and any other content produced by the deleted profile).

[b][color=grey][size=20]Content Creation[/size][/color][/b]

[b][color=white]Writing Posts[/color][/b]

Red supports a number of different ways of adding content, from a graphical text editor, to various types of markup and pure HTML.

Red bundles the TinyMCE rich text editor, which can be turned on under &quot;Settings.&quot;
For user who prefer not to use TinyMCE, content can be entered by typing BBCode markup.
Furthermore, when creating &quot;Websites&quot; or using &quot;Comanche&quot; and its PCL[FINISH], content can be entered in HTML, Markdown and plain text.

[b][color=white]Deletion of content[/color][/b]
Any content created in the Red Matrix remains under the control of the user (or channel) that originally created.  At any time, a user can delete a message, or a range of messages.  The deletion process ensures that the content is deleted, regardless of whether it was posted on a channel's primary (home) hub, or on another hub, where the channel was remotely authenticated via Zot.

[b][color=white]Media[/color][/b]
Similar to any other modern blogging system, social network, or a micro-blogging service, Red supports the uploading of files, embedding of videos, linking web pages.

[b][color=white]Previewing[/color][/b] 
Post can be previewed prior to sending.

Return to the [url=[baseurl]/help/main]Main documentation page[/url]