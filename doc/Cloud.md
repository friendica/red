Personal Cloud Storage
======================


The Red Matrix provides an ability to store privately and/or share arbitrary files with friends.

You may either upload files from your computer into your storage area, or copy them directly from the operating system using the WebDAV protocol.

On many public servers there may be limits on disk usage. 


**File Attachments**

The quickest and easiest way to share files is through file attachments. In the row of icons below the status post editor is a tool to upload attachments. Click the tool, select a file and submit. After the file is uploaded, you will see an attachment code placed inside the text region. Do not edit this line or it may break the ability for your friends to see the attachment. You can use the post permissions dialogue box or privacy hashtags to restrict the visibility of the file - which will be set to match the permissions of the post your are sending.

To delete attachments or change the permissions on the stored files, visit "filestorage/$channel_nickname" replacing $channel_nickname with the nickname you provided during channel creation. 

**Web Access**

Your files are visible on the web at the location "cloud/$channel_nickname" to anybody who is allowed to view them. If the viewer has sufficient privileges, they may also have the ability to create new files and folders/directories.


**WebDAV access**

This varies by operating system. In Windows, open the Windows Explorer (file manager) and type "$yoursite\cloud\$channel_nickname" into the address bar. Replace $yoursite with the URL to your Red Matrix hub and $channel_nickname with the nickname for your channel.

You will be prompted for a username and password. For the username, you may supply either your account email address (which will select the default channel), or a channel nickname for the channel you wish to authenticate as. In either case use your account password for the password field. You will then be able to copy, drag/drop, edit, delete and otherwise work with files as if they were an attached disk drive.


**Permissions**

When using WebDAV, the file is created with your channel's default file permissions and this cannot be changed from within the operating system. It also may not be as restrictive as you would like. What we've found is that the preferred method of making files private is to first create folders or directories; then visit "filestorage/$channel_nickname"; select the directory and change the permissions. Do this before you put anything into the directory. The directory permissions take precedence so you can then put files or other folders into that container and they will be protected from unwanted viewers by the directory permissions. It is common for folks to create a "personal" or "private" folder which is restricted to themselves. You can use this as a personal cloud to store anything from anywhere on the web or any computer and it is protected from others. You might also create folders for "family" and "friends" with permission granted to appropriate collections of channels.    


 
  