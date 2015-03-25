##Diaspora Compatibility

Diaspora protocol compatibility is presently considered an ***experimental*** feature. It may not be available on all sites and presents some serious compatibility issues with redmatrix. At the moment these compatibility issues will be shared with "Friendica-over-Diaspora" protocol communications. 

Private mail retraction (unsend) will not be possible on Diaspora. 

Private posts and their associated comments are sent in plaintext email notifications in Diaspora and Friendica. This is a major privacy issue and affects any private communications you have where *any* member of the conversation is on another network. Be aware of it. 

Access control only works on posts and comments. Diaspora members will get permission denied trying to access any other access controlled redmatrix objects such as files, photos, webpages, chatrooms, etc. In the case of private photos that are linked to posts, they will see a "prohibited sign" instead of the photo. Diaspora has no concept of private media. There is no workaround except to make your media resources public (to everybody on the internet).


Edited posts will not be delivered. Diaspora members will see the original post/comment without edits. There is no mechanism in the protocol to update an existing post. We cannot delete it and submit another invisibly because the message-id will change and we need to keep the same message-id on our own network. The only workaround is to delete the post/comment and do it over. We may eventually provide a way to delete the out of date copy only from Diaspora and keep it intact on networks that can handle edits. 

Some comments from external services will not deliver to Diaspora, as they have no Diaspora service discovery. Currently this applies to comments from WordPress blogs which are imported into your stream; but will extend to most any service that has no Diaspora discover mechanism. 


Nomadic identity will not work with Diaspora. We will eventually provide an **option** which will allow you to "start sharing" from all of your clones when you make the first connection. The Diaspora person does not have to accept this, but it will allow your communications to continue if they accept this connection. Without this option, if you go to another server from where you made the connection originally or you make the connection before creating the clone, you will need to make friends with them again from the new location. 

Post expiration is not supported on Diaspora. We will provide you an option to not send expiring posts to that network. In the future this may be provided with a remote delete request. 

End-to-end encryption is not supported. We will translate these posts into a lock icon, which can never be unlocked from the Diaspora side. 

Message verification will eventually be supported. 

Multiple profiles are not supported. Diaspora members can only see your default profile.

Birthday events will not appear in Diaspora. Other events will be translated and sent as a post, but all times will either be in the origination channel's timezone or in GMT. We do not know the recipient's timezone because Diaspora doesn't have this concept. 

We currently allow tags to be hijacked by default. We will provide an option to allow you to prevent the other end of the network from hijacking your tags and point them at its own resources. 

Community tags will not work. We will send a tagging activity as a comment. It won't do anything.  

Privacy tags (@!somebody) will not be available to Diaspora members. These tags may have to be stripped or obscured to prevent them from being hijacked - which could result in privacy issues.  

Plus-tagged redmatrix forums should work from Diaspora. 

Premium channel redirects will not be sent. If you allow Diaspora connections, they will not see that you have a premium channel. 

You cannot use Diaspora channels as channel sources. 


Dislikes of posts will be converted to comments and you will have the option to send these as comments or not send them to Diaspora (which does not provide dislike). Currently they are not sent.

We will do the same for both likes and dislikes of comments. They can either be sent as comments or you will have the ability to prevent them from being transmitted to Diaspora. Currently they are not sent. 


"observer tags" will be converted to empty text. 


Embedded apps will be translated into links.


Embedded page design elements (work in progress) will be either stripped or converted to an error message. 

Diaspora members will not appear in the directory. 


There are differences in oembed compatibility between the networks. Some embedded resources will turn into a link on one side or the other.  

#include doc/macros/main_footer.bb;
