[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]id[/td][td][/td]generated index[td]int(11)[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]name[/td][td]plugin base (file)name[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]version[/td][td]currently unused[/td][td]char(255)[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]installed[/td][td]currently always 1[/td][td]tinyint(1)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]hidden[/td][td]currently unused[/td][td]tinyint(1)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]timestamp[/td][td]file timestamp to check for reloads[/td][td]bigint(20)[/td][td]NO[/td][td][/td][td]0[/td][td]
[/td][/tr]
[tr][td]plugin_admin[/td][td]1 = has admin config, 0 = has no admin config[/td][td]tinyint(1)[/td][td]NO[/td][td][/td][td]0[/td][td]
[/td][/tr]
[/table]

Notes:

These are addons which have been enabled by the site administrator on the admin/plugin page

Return to [zrl=[baseurl]/help/database]database documentation[/zrl]