File system layout:
===================

[addon]         optional addons/plugins

[boot.php]      Every process uses this to bootstrap the application structure

[doc]           Help Files

[images]        core required images

[include]       The "model" in MVC - (back-end functions), also contains PHP "executables" for background processing

[index.php]     The front-end controller for web access

[install]		Installation and upgrade files and DB schema

[js]            core required javascript

[library]       Third party modules (must be license compatible)

[mod]           Controller modules based on URL pathname (e.g. http://sitename/foo loads mod/foo.php)

[spec]          protocol specifications

[util]          translation tools, main English string database and other miscellaneous utilities

[version.inc]   contains current version (auto-updated via cron for the master repository and distributed via git)

[view]          theming and language files


[view/(css,js,img,php,tpl)] default theme files

[view/(en,it,es ...)]  language strings and resources

[view/theme/]  individual named themes containing (css,js,img,php,tpl) over-rides 


The Database:
=============

* abook - contact table, replaces Friendica 'contact'
* account - service provider account 
* addon - registered plugins
* attach - file attachments
* auth_codes - OAuth usage
* cache - TBD
* challenge - old DFRN structure, may re-use or may deprecate
* channel - replaces Friendica 'user'
* clients - OAuth usage
* config - main configuration storage
* event - Events
* fcontact - friend suggestion stuff
* ffinder - friend suggestion stuff
* fserver - obsolete
* fsuggest - friend suggestion stuff
* gcign - ignored friend suggestions
* gcontact - social graph storage, obsolete
* glink - social graph storage - obsolete
* group - privacy groups
* group_member - privacy groups
* hook - plugin hook registry
* hubloc - Red location storage, ties a location to an xchan
* intro - DFRN introductions, may be obsolete
* item - posts
* item_id - other identifiers on other services for posts
* mail - private messages
* manage - may be unused in Red, table of accounts that can "su" each other
* notify - notifications
* notify-threads - need to factor this out and use item thread info on notifications
* outq - Red output queue
* pconfig - personal (per channel) configuration storage
* photo - photo storage
* profile - channel profiles
* profile_check - DFRN remote auth use, may be obsolete
* queue - old Friendica queue, obsolete
* register - registrations requiring admin approval
* session - web session storage
* site - site table to find directory peers
* spam - unfinished
* term - item taxonomy (categories, tags, etc.) table
* tokens - OAuth usage
* verify - general purpose verification structure
* xchan - replaces 'gcontact', list of known channels in the universe 
* xlink - "friends of friends" linkages derived from poco
* xprof - if this hub is a directory server, contains basic public profile info of everybody in the network
* xtag - if this hub is a directory server, contains tags or interests of everybody in the network


How to theme Red - by Olivier Migeot
====================================

This is a short documentation on what I found while trying to modify Red's appearance.

First, you'll need to create a new theme. This is in /view/theme, and I chose to copy 'redbasic' since it's the only available for now. Let's assume I named it <theme>.

Oh, and don't forget to rename the _init function in <theme>/php/theme.php to be <theme>_init() instead of redbasic_init().

At that point, if you need to add javascript or css files, add them to <theme>/js or <theme>/css, and then "register" them in <theme>_init() through head_add_js('file.js') and head_add_css('file.css').

Now you'll probably want to alter a template. These can be found in in /view/tpl OR view/<theme>/tpl. All you should have to do is copy whatever you want to tweak from the first place to your theme's own tpl directory.



