[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]id[/td][td]generated index[/td][td]int(10) unsigned[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]aid[/td][td]account_id of owner[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]uid[/td][td]channel_id of owner[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]hash[/td][td]hash for cross-site identification[/td][td]char(64)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]creator[/td][td]xchan_hash of author/creator[/td][td]char(128)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]filename[/td][td]filename of original[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]filetype[/td][td]mimetype[/td][td]char(64)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]filesize[/td][td]size in bytes[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]revision[/td][td]for version control (partially implemented)[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]folder[/td][td]attach.hash of parent folder[/td][td]char(64)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]flags[/td][td]see notes[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]data[/td][td]file data or pathname to stored data if ATTACH_FLAG_OS[/td][td]longblob[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]created[/td][td]creation time[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]edited[/td][td]last edit time[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]allow_cid[/td][td]permissions[/td][td]mediumtext[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]allow_gid[/td][td]permissions[/td][td]mediumtext[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]deny_cid[/td][td]permissions[/td][td]mediumtext[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]deny_gid[/td][td]permissions[/td][td]mediumtext[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[/table]


Bitmasks

define ( 'ATTACH_FLAG_DIR',    0x0001);  This is a directory
define ( 'ATTACH_FLAG_OS',     0x0002);  Data content is link to OS file containing data, if unset the data filed contains the file data

permissions are xchan_hash or group_hash surrounded by angle chars. e.g. '<abc123><xyz789>'

Return to [zrl=[baseurl]/help/database]database documentation[/zrl]