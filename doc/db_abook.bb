[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]abook_id[/td][td]Sequential ID[/td][td]int(10) unsigned[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]abook_account[/td][td]account.account_id of the channel which owns this record[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]abook_channel[/td][td]channel.channel_id of the channel which owns this record[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]abook_xchan[/td][td]xchan.xchan_hash of the target identity (this channel's connection)[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]abook_my_perms[/td][td]bitfield of all specific permissions granted this connection[/td][td]int(11)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]abook_their_perms[/td][td]bitfield of all permissions granted to you by this connection[/td][td]int(11)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]abook_closeness[/td][td]"closeness" value for optional affinity tool, 0-99[/td][td]tinyint(3) unsigned[/td][td]NO[/td][td]MUL[/td][td]99[/td][td]
[/td][/tr]
[tr][td]abook_rating[/td][td]The channel owner's (public) rating of this connection -10 to +10, default 0 or unrated[/td][td]int(11)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]abook_rating_text[/td][td]The channel owner's (public) rating of this connection free form text[/td][td]text[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]abook_created[/td][td]Datetime this record was created[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]abook_updated[/td][td]Datetime this record was modified[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]abook_connected[/td][td]datetime of last successful "poll" for this connection[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]abook_dob[/td][td]Datetime of connection's birthday converted from *their* timezone to UTC[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]abook_flags[/td][td]Bitfield containing blocked(0x1), ignored(0x2), hidden(0x4), archived(0x8), pending(0x10), unconnected(0x20), self(0x80), feed(0x100)[/td][td]int(11)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]abook_profile[/td][td]profile.guid of profile to display to this connection if authenticated[/td][td]char(64)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[/table]


Notes: 

ABOOK_FLAGS_BLOCKED - Bi-directional communications with this channel are blocked, regardless of other permissions. 

ABOOK_FLAGS_IGNORED - Incoming communications from this channel are blocked, regardless of other permissions.  

ABOOK_FLAGS_HIDDEN - This connection will not be shown as a connection to anybody but the channel owner

ABOOK_FLAGS_ARCHIVED - This connection is likely non-functioning and the entry and conversations are preserved, but further polled communications will not be attempted. 

ABOOK_FLAGS_PENDING - A connection request was received from this channel but has not been approved by the channel owner, public communications may still be visible but no additional permissions have been granted. 

ABOOK_FLAGS_UNCONNECTED - currently unused. Projected usage is to indicate "one-way" connections which were insitgated on this end but are still pending on the remote end. 

ABOOK_FLAGS_SELF is a special case where the owner is the target. Every channel has one abook entry with ABOOK_FLAGS_SELF with a target abook_xchan set to channel.channel_hash . When this flag is present, abook_my_perms is the default permissions granted to all new connections and several other fields are unused.

ABOOK_FLAGS_FEED - indicates this connection is an RSS/Atom feed and may trigger special handling.

Return to [zrl=[baseurl]/help/database]database documentation[/zrl]