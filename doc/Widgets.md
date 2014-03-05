Core Widgets
============

Some/many of these widgets have restrictions which may restrict the type of page where they may appear or may require login


* profile - displays a profile sidebar on pages which load profiles (pages with nickname in the URL)

* tagcloud - display a tagcloud of webpage items

    * args: count - number of items to return (default 24)
    *


* collections - collection selector for the current logged in channel

    * args: mode - one of "conversation", "group", "abook" depending on module
    *


* suggestions - friend suggestions for the current logged on channel

* follow - presents a text box for following another channel

* notes - private notes area for the current logged in channel if private_notes feature is enabled

* savedsearch - network/matrix search with save - must be logged in and savedsearch feature enabled

* filer - select filed items from network/matrix stream - must be logged in

* archive - date range selector for network and channel pages

* fullprofile - same as profile currently

* categories - categories filter (channel page)

* tagcloud_wall - tagcloud for channel page only

* affinity - affinity slider for network page - must be logged in

* settings_menu - sidebar menu for settings page, must be logged in

* mailmenu - sidebar menu for private message page - must be logged in

* design_tools - design tools menu for webpage building pages, must be logged in

* findpeople - tools to find other channels

* photo_albums - list photo albums of the current page owner with a selector menu

* vcard - mini profile sidebar for the person of interest (page owner, whatever)

* dirsafemode - directory selection tool - only on directory pages

* dirsort - directory selection tool - only on directory pages

* dirtags - directory tool - only on directory pages

* menu_preview - preview a menu - only on menu edit pages

* chatroom_list - list of chatrooms for the page owner

* bookmarkedchats - list of bookmarked chatrooms collected on this site for the current observer

* suggestechats - "interesting" chatrooms chosen for the current observer

* item - displays a single webpage item by mid
1 args: mid - message_id of webpage to display

