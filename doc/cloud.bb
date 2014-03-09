[b]Personal Cloud Storage[/b]

The Red Matrix provides an ability to store privately and/or share arbitrary files with friends.

You may either upload files from your computer into your storage area, or copy them directly from the operating system using the WebDAV protocol.

On many public servers there may be limits on disk usage.

[b]File Attachments[/b]

The quickest and easiest way to share files is through file attachments. In the row of icons below the status post editor is a tool to upload attachments. Click the tool, select a file and submit. After the file is uploaded, you will see an attachment code placed inside the text region. Do not edit this line or it may break the ability for your friends to see the attachment. You can use the post permissions dialogue box or privacy hashtags to restrict the visibility of the file - which will be set to match the permissions of the post your are sending.

To delete attachments or change the permissions on the stored files, visit [observer.baseurl]/filestorage/{{username}}&quot; replacing {{username}} with the nickname you provided during channel creation.

[b]Web Access[/b]

Your files are visible on the web at the location &quot;cloud/{{username}}&quot; to anybody who is allowed to view them. If the viewer has sufficient privileges, they may also have the ability to create new files and folders/directories.

[b]WebDAV access[/b]

See: [zrl=[baseurl]/help/cloud_desktop_clients]Cloud Desktop Clients[/zrl]

[b]Permissions[/b]

When using WebDAV, the file is created with your channel's default file permissions and this cannot be changed from within the operating system. It also may not be as restrictive as you would like. What we've found is that the preferred method of making files private is to first create folders or directories; then visit &quot;filestorage/{{username}}&quot;; select the directory and change the permissions. Do this before you put anything into the directory. The directory permissions take precedence so you can then put files or other folders into that container and they will be protected from unwanted viewers by the directory permissions. It is common for folks to create a &quot;personal&quot; or &quot;private&quot; folder which is restricted to themselves. You can use this as a personal cloud to store anything from anywhere on the web or any computer and it is protected from others. You might also create folders for &quot;family&quot; and &quot;friends&quot; with permission granted to appropriate collections of channels.