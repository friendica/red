**Permissions** 

Permissions in the Red Matrix are more complete than you may be used to.  This allows us to define more fine graded relationships than the black and white "this person is my friend, so they can do everything" or "this person is not my friend, so they can't do anything" permissions you may find elsewhere.

**Default Permissions**

On your settings page, you will find a list of default permissions.  These permissions are automatically applied to everybody unless you specify otherwise.  The scope of these permissions varies from "Only me" to "Everybody" - though some scopes may not be available for some permissions.  For example, you can't allow "anybody on the internet" to send you private messages, because we'd have no way to identify the sender, or offer a return.

The scopes of permissions are:

- _Nobody Except Yourself_.  This is self explanatory.  Only you will be allowed to use this permission.
- _Only those you specifically allow_.  By default, people you are not connected to, and all new contacts will have this permission denied.  You will be able to make exceptions for individual channels on their contact edit screen.
- _Anybody in your address book_.  Anybody you do not know will have this permission denied, but anybody you accept as a contact will have this permission approved.  This is the way most legacy platforms handle permissions.
- _Anybody On This Website_.  Anybody using the same website as you will have permission approved.  Anybody who registered at a different site will have this permission denied.
- _Anybody in this network_.  Anybody who has got zot will have this permission approved.  Even complete strangers.  However, anybody not logged in/authenticated will have this permission denid.
- _Anybody on the internet_.  Completely public.  This permission will be approved for anybody at all.

The individual permissions are:

- _Can view my "public" stream and posts_.  This permision determines who can view your channel "stream" that is, the non-private posts that appear on the "home" tab when you're logged in.
- _Can view my "public" channel profile_.  This permission determines who can view your channel's profile.  This refers to the "about" tab
- _Can view my "public" photo albums_.  This permission determines who can view your photo albums.  Individual photographs may still be posted to a more private audience.
- _Can view my "public" address book_. This permission determines who can view your contacts.  These are the connections displayed in the "View connections" section.
- _Can view my "public" file storage_. This permission determines who can view your public files.  This isn't done yet, so this is placeholder text.
- _Can view my "public" pages_.  This permission determines who can view your public web pages.  This isn't done yet, so this is placeholder text.
- _Can send me their channel stream and posts_.  This permission determines whose posts you will view.  If your channel is a personal channel (ie, you as a person), you would probably want to set this to "anyone in my address book" at a minimum.  A forum page would probably want to choose "nobody except myself".  Setting this to "Anybody in the network" will show you posts from complete strangers, which is a good form of discovery.
- _Can post on my channel page ("wall")_.  This permission determines who can write to your wall when clicking through to your channel.  
- _Can comment on my posts_.  This permission determines who can comment on posts you create.  Normally, you would want this to match your "can view my public pages" permission
- _Can send me private mail messages_.  This determines who can send you private messages (zotmail).
- _Can post photos to my photo albums_.  This determines who can post photographs in your albums.  This is very useful for forum-like channels where connections may not be connected to each other.
- _Can forward to all my channel contacts via post tags_. Using @- mentions will reproduce a copy of your post on the profile specified, as though you posted on the channel wall.  This determines if people can post to your channel in this way.
- _Can chat with me (when available)_.  This determines who can (start?) a chat with you.  This isn't done yet, so is placeholder text.
- _Can write to my "public" file storage_. This determines who can upload files to your public file storage.  This isn't done yet, so this is placeholder text.
- _Can edit my "public" pages_.  This determines who can edit your webpages.  This is useful for wikis or sites with multiple editors, but this isn't done yet, so this is placeholder text.
- _Can administer my channel resources_.  This determines who can have full control of your channel.  This should normally be set to "nobody except myself".

If you have set any of these permissions to "only those I specifically allow", you may specify indivudal permissions on the contact edit screen.

**Affinity**

The contact edit screen offers a slider to select a degree of friendship with the contact.  Think of this as a measure of how much you dislike them.  1 is for people you like, whose posts you want to see all the time.  99 is for people you can't stand, whose posts you never want to see.  Once you've assigned a value here, you can use the affinity tool (assuming you have it enabled) on the network page to filter content based on this number.  

The slider has both a minimum and maximum value.  Posts will only be shown from people who fall between this range.  Affinity has no relation to permissions, and is only useful in conjunction with the affinity tool feature.
