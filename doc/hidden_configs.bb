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
