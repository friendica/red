[b]Advanced Configurations for Administrators[/b]

RedMatrix contains many configuration options hidden from the main admin panel.
These are generally options considered too niche, confusing, or advanced for 
the average member.  These settings can be activated from the the top level Red 
directory with the syntax [code]util/config cat key value[/code] for a site
configuration, or [code]util/pconfig channel_id cat key value[/code] for a 
member configuration.

This document assumes you're an administrator.

[b]pconfig[/b]
    [b]system > user_scalable[/b]
        Determine if the app is scalable on touch screens.  Defaults to on, to 
        disable, set to zero - real zero, not just false.
    [b]system > always_my_theme[/b]
        Always use your own theme when viewing channels on the same hub.  This
        will break in some quite imaginative ways when viewing channels with 
        theme dependent Comanche.
    [b]system > paranoia[/b]
        Sets the security level of IP checking. If the IP address of a logged-in session changes
		apply this level to determine if the account should be logged out as a security breach.
            Options are: 
                    0 - no IP checking 
                    1 - check 3 octets
                    2 - check 2 octets
                    3 - check for any difference at all
    [b]system > prevent_tag_hijacking[/b]
        Prevent foreign networks hijacking hashtags in your posts and directing them at its own resources.
    [b]system > blocked[/b]
        An array of xchans blocked by this channel.  Technically, this is a
        hidden config and does belong here, however, addons (notably 
        superblock) have made this available in the UI.
    [b]system > default_cipher[/b]
        Set the default cipher used for E2EE items.
    [b]system > network_page_default[/b]
        Set default params when viewing the network page.  This should contain
        the same querystring as manual filtering.
    [b]system > display_friend_count[/b]
        Set the number of connections to display in the connections profile 
        widget.
    [b]system > taganyone[/b]
        Requires the config of the same name to be enabled.  Allow the @mention tagging
        of anyone, whether you are connected or not.  This doesn't scale.
    [b]system > startpage[/b]
        Another of those technically hidden configs made available by addons.
        Sets the default page to view when logging in.  This is exposed to the
        UI by the startpage addon.
    [b]system > forcepublicuploads[/b]
        Force uploaded photos to be public when uploaded as wall items.  It
        makes far more sense to just set your permissions properly in the first
        place.  Do that instead.
    [b]system > do_not_track[/b]
        As the browser header.  This will break many identity based features.  
        You should really just set permissions that make sense.

[b]Site config[/b]
    [b]system > taganyone[/b]
        Allow the @mention tagging of anyone whether you are connected or not.
    [b]system > directorytags[/b]
        Set the number of keyword tags displayed on the directory page.
    [b]system > startpage[/b]
        Set the default page to be taken to after a login for all channels at
        this website.  Can be overwritten by user settings.
    [b]system > projecthome[/b]
        Set the project homepage as the homepage of your hub.
    [b]system > workflowchannelnext[/b]
        The page to direct users to immediately after creating a channel.
    [b]system > max_daily_registrations[/b]
        Set the maximum number of new registrations allowed on any day.
        Useful to prevent oversubscription after a bout of publicity
        for the project.
    [b]system > tos_url[/b]
        Set an alternative link for the ToS location.
    [b]system > block_public_search[/b]
        Similar to block_public, except only blocks public access to 
        search features.  Useful for sites that want to be public, but
        keep getting hammered by search engines.
    [b]system > paranoia[/b]
        As the pconfig, but on a site-wide basis.  Can be overwritten
        by member settings.
    [b]system > openssl_conf_file[/b]
        Specify a file containing OpenSSL configuration.  Read the code first.
        If you can't read the code, don't play with it.
    [b]system > optimize_items[/b]
        Runs optimise_table during some tasks to keep your database nice and 
        defragmented.  This comes at a performance cost while the operations
        are running, but also keeps things a bit faster while it's not.  
        There also exist CLI utilities for performing this operation, which you
        may prefer, especially if you're a large site.
	[b]system > expire_limit
		Don't expire any more than this number of posts per channel per
		expiration run to keep from exhausting memory. Default 5000.
    [b]system > dlogfile[/b]
        Logfile to use for logging development errors.  Exactly the same as
        logger otherwise.  This isn't magic, and requires your own logging
        statements.  Developer tool.
    [b]system > authlog[/b]
        Logfile to use for logging auth errors.  Used to plug in to server
        side software such as fail2ban.  Auth failures are still logged to
        the main logs as well.
    [b]system > hide_in_statistics[/b]
        Tell the red statistics servers to completely hide this hub in hub lists.
    [b]system > reserved_channels[/b]
        Don't allow members to register channels with this comma separated
        list of names (no spaces)
    [b]system > auto_follow[/b]
        Make the first channel of an account auto-follow channels listed here - comma separated list of webbies (member@hub addresses).
    [b]system > admin_email[/b]
        Specifies the administrator's email for this site.  This is initially set during install.
    [b]system > cron_hour[/b]
        Specify an hour in which to run cron_daily.  By default with no config, this will run at midnight UTC.
    [b]system > minimum_feedcheck_minutes[/b]
        The minimum interval between polling RSS feeds.  If this is lower than the cron interval, feeds will be polled with each cronjob
    [b]system > blacklisted_sites[/b]
        An array of specific hubs to block from this hub completely.
    [b]system > ignore_imagick[/b]
        Ignore imagick and use GD, even if imagick is installed on the server. Prevents some issues with PNG files in older versions of imagick.
    [b]system > no_age_restriction[/b]
        Do not restrict registration to people over the age of 13. This carries legal responsibilities in many countries to require that age be provided and to block all personal information from minors, so please check your local laws before changing.  
    [b]system > override_poll_lockfile[/b]
        Ignore the lock file in the poller process to allow more than one process to run at a time.
    [b]system > projecthome[/b]
        Display the project page on your home page for logged out viewers.
    [b]system > sellpage[/b]
        A URL shown in the public sites list to sell your hub - display service classes, etc.
    [b]randprofile > check[/b]
        When requesting a random profile, check that it actually exists first
    [b]randprofile > retry[/b]
        Number of times to retry getting a random profile
    [b]system > photo_cache_time[/b]
        How long to cache photos, in seconds. Default is 86400 (1 day).
        Longer time increases performance, but it also means it takes longer for changed permissions to apply.
	[b]system > poco_rating_enable[/b]
		Distributed reputation reporting and data collection may be disabled. If your site does not participate in distributed reputation you will also not be able to make use of the data from your connections on other sites. By default and in the absence of any setting it is enabled. Individual members can opt out by restricting who can see their connections or by not providing any reputation information for their connections. 
		
#include doc/macros/main_footer.bb;

