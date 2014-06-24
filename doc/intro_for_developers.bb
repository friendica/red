[b]Red Developer Guide[/b]

[b]File system layout:[/b]

[addon] optional addons/plugins

[boot.php] Every process uses this to bootstrap the application structure

[doc] Help Files

[images] core required images

[include] The &quot;model&quot; in MVC - (back-end functions), also contains PHP &quot;executables&quot; for background processing

[index.php] The front-end controller for web access

[install] Installation and upgrade files and DB schema

[js] core required javascript

[library] Third party modules (must be license compatible)

[mod] Controller modules based on URL pathname (e.g. #^[url=http://sitename/foo]http://sitename/foo[/url] loads mod/foo.php)

[spec] protocol specifications

[util] translation tools, main English string database and other miscellaneous utilities

[version.inc] contains current version (auto-updated via cron for the master repository and distributed via git)

[view] theming and language files

[view/(css,js,img,php,tpl)] default theme files

[view/(en,it,es ...)] language strings and resources

[view/theme/] individual named themes containing (css,js,img,php,tpl) over-rides

[b]The Database:[/b]

    [li]abook - contact table, replaces Friendica 'contact'[/li]
    [li]account - service provider account[/li]
    [li]addon - registered plugins[/li]
    [li]attach - file attachments[/li]
    [li]auth_codes - OAuth usage[/li]
    [li]cache - TBD[/li]
    [li]challenge - old DFRN structure, may re-use or may deprecate[/li]
    [li]channel - replaces Friendica 'user'[/li]
    [li]clients - OAuth usage[/li]
    [li]config - main configuration storage[/li]
    [li]event - Events[/li]
    [li]fcontact - friend suggestion stuff[/li]
    [li]ffinder - friend suggestion stuff[/li]
    [li]fserver - obsolete[/li]
    [li]fsuggest - friend suggestion stuff[/li]
    [li]gcign - ignored friend suggestions[/li]
    [li]gcontact - social graph storage, obsolete[/li]
    [li]glink - social graph storage - obsolete[/li]
    [li]group - privacy groups[/li]
    [li]group_member - privacy groups[/li]
    [li]hook - plugin hook registry[/li]
    [li]hubloc - Red location storage, ties a location to an xchan[/li]
    [li]intro - DFRN introductions, may be obsolete[/li]
    [li]item - posts[/li]
    [li]item_id - other identifiers on other services for posts[/li]
    [li]mail - private messages[/li]
    [li]manage - may be unused in Red, table of accounts that can &quot;su&quot; each other[/li]
    [li]notify - notifications[/li]
    [li]notify-threads - need to factor this out and use item thread info on notifications[/li]
    [li]outq - Red output queue[/li]
    [li]pconfig - personal (per channel) configuration storage[/li]
    [li]photo - photo storage[/li]
    [li]profile - channel profiles[/li]
    [li]profile_check - DFRN remote auth use, may be obsolete[/li]
    [li]queue - old Friendica queue, obsolete[/li]
    [li]register - registrations requiring admin approval[/li]
    [li]session - web session storage[/li]
    [li]site - site table to find directory peers[/li]
    [li]spam - unfinished[/li]
    [li]term - item taxonomy (categories, tags, etc.) table[/li]
    [li]tokens - OAuth usage[/li]
    [li]verify - general purpose verification structure[/li]
    [li]xchan - replaces 'gcontact', list of known channels in the universe[/li]
    [li]xlink - &quot;friends of friends&quot; linkages derived from poco[/li]
    [li]xprof - if this hub is a directory server, contains basic public profile info of everybody in the network[/li]
    [li]xtag - if this hub is a directory server, contains tags or interests of everybody in the network[/li]

    
[b]How to theme Red - by Olivier Migeot[/b]

This is a short documentation on what I found while trying to modify Red's appearance.

First, you'll need to create a new theme. This is in /view/theme, and I chose to copy 'redbasic' since it's the only available for now. Let's assume I named it .

Oh, and don't forget to rename the _init function in /php/theme.php to be _init() instead of redbasic_init().

At that point, if you need to add javascript or css files, add them to /js or /css, and then &quot;register&quot; them in _init() through head_add_js('file.js') and head_add_css('file.css').

Now you'll probably want to alter a template. These can be found in in /view/tpl OR view//tpl. All you should have to do is copy whatever you want to tweak from the first place to your theme's own tpl directory.