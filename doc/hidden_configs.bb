[b]Advanced Configurations for Power Users[/b]

RedMatrix contains many configuration options hidden from the main admin panel.
These are generally options considered too niche, confusing, or advanced for 
the average user.  These settings can be activated from the the top level Red 
directory with the syntax [code]util/config cat key value[/code] for a site
configuration, or [code]util/pconfig channel_id cat key value[/code] for a 
member configuration.

This document assumes you're an actual Power User.

[b]pconfig[/b]
    [em]system > user_scalable[/em]
        Determine if the app is scalable on touch screens.  Defaults to on, to 
        disable, set to zero - real zero, not just false.
    [em]system > always_my_theme[/em]
        Always use your own theme when viewing channels on the same hub.  This
        will break in some quite imaginative ways when viewing channels with 
        theme dependent Comanche.
    [em]system > paranoia[/em]
        Sets the security level of IP checking 
            Options are: 
                    0 - no IP checking 
                    1 - check 3 octets
                    2 - check 2 octets
                    3 - check for any difference at all
    [em]system > protect_tag_hijacking[/em]
        Prevent foreign networks hijacking system tags for your posts.
    [em]system > archive_removed_contacts[/em]
[color=red]I don't think ths works.  Check it before linking from the index then delete this line.[/color]
        When an connection is removed, don't delete everything, instead, 
        archive all their posts.
    [em]system > blocked[/em]
        An array of xchans blocked by this channel.  Technically, this is a
        hidden config and does belong here, however, addons (notably 
        superblock) have made this available in the UI.
    [em]system > default_cipher[/em]
        Set the default cipher used for E2EE items.
    [em]system > network_page_default[/em]
        Set default params when viewing the network page.  This should contain
        the same querystring as manual filtering.
    [em]system > display_friend_count[/em]
        Set the number of connections to display in the connections profile 
        widget.
    [em]system > taganyone[/em]
        Requires the config of the same name to be enabled.  Allow the tagging
        of anyone, whether you are connected or not.  This doesn't scale.
    [em]system > startpage[/em]
        Another of those technically hidden configs made available by addons.
        Sets the default page to view when logging in.  This is exposed to the
        UI by the startpage addon.
    [em]system > forcepublicuploads[/em]
        Force uploaded photos to be public when uploaded as wall items.  It
        makes far more sense to just set your permissions properly in the first
        place.  Do that instead.
    [em]system > do_not_track[/em]
        As the browser header.  This will break many identity based features.  
        You should really just set permissions that make sense.

[b]Site config[/b]
    [em]system > taganyone[/em]
        Allow the tagging of anyone wehter you are connected or not.
    [em]system > directorytags[/em]
        Set the number of tags displayed on the directory page.
    [em]system > startpage[/em]
        Set the default page to be taken to after a login for all channels at
        this website.  Can be overwritten by user settings.
    [em]system > proejcthome[/em]
        Set the project homepage as the homepage of your hub.
    [em]system > workflowchannelnext[/em]
        The page to direct users to immediately after creating a channel.
    [em]system > max_bookmark_images[/em]
        Set the maximum number of images to use when parsing a link.
[color=red]Not sure this does anything.  It defaults to 2, I've never seen more than one.  Verify before linking from index[/color]
    [em]system > max_daily_registrations[/em]
        Set the maximum number of new registrations allowed on any day.
        Useful to prevent oversubscription after a bout of publicity
        for the project.
    [em]system > tos_url[/em]
        Set an alternative link for the ToS location.
    [em]system > block_public_search[/em]
        Similar to block_public, except only blocks public access to 
        search features.  Useful for sites that want to be public, but
        keep getting hammered by search engines.
    [em]system > paranoia[/em]
        As the pconfig, but on a site-wide basis.  Can be overwritten
        by member settings.
    [em]system > openssl_conf_file[/em]
        Specify a file containing OpenSSL configuration.  Read the code first.
        If you can't read the code, don't play with it.
    [em]system > optimize_items[/em]
        Runs optimise_table during some tasks to keep your database nice and 
        defragmented.  This comes at a performance cost while the operations
        are running, but also keeps things a bit faster while it's not.  
        There also exist CLI utilities for performing this operation, which you
        may prefer, especially if you're a large site.
    [em]system > default_expire_days[/em]
        When creating a new channel, set the default expiration of connections
        posts to this number of days.
    [em]system > dlogfile[/em]
        Logfile to use for logging development errors.  Exactly the same as
        logger otherwise.  This isn't magic, and requires your own logging
        statements.  Developer tool.
    [em]system > authlog[/em]
        Logfile to use for logging auth errors.  Used to plug in to server
        side software such as fail2ban.  Auth failures are still logged to
        the main logs as well.
