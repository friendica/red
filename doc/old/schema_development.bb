[b]Red Development - A Guide To The Schema System[/b]

A schema, in a nutshell, is a collection of settings for a bunch of variables to define
certain elements of a theme.  A schema is loaded as though it were part of config.php
and has access to all the same information.  Importantly, this means it is identity aware,
and can be used to do some interesting things.  One could, for example, restrict options
by service class, or present different options to different members.

By default, we filter only by whether or not expert mode is enabled.  If expert mode is
enabled, all options are presented to the member.  If it is not, only scheme, background
image, font face, and iconset are available as choices.

A schema is loaded *after* the member's personal settings.  Therefore, to allow a member
to overwrite a particular aspect of a schema you would use the following syntax:
[code]
        if (! $foo)
            $foo = 'bar';
[/code]
However, there are circumstances - particularly with positional elements - where it
may be desirable (or necessary) to override a member's settings.  In this case, the syntax
is even simpler:
[code]
            $foo = 'bar';
[/code]
Members will not thank you for this, however, so only use it when it is required.

If no personal options are set, and no schema is selected, we will first try to load a schema
with the file name &quot;default.php&quot;.  This file should never be included with a theme.  If it
is, merge conflicts will occur as people update their code.  Rather, this should be defined
by administrators on a site by site basis.

You schema does not need to - and should not - contain all of these values.  Only the values
that differ from the defaults should be listed.  This gives you some very powerful options
with very few lines of code.

Note the options available differ with each theme.  The options available with the Redbasic 
theme are as follows:

[li] nav_colour
	The colour of the navigation bar.  Options are red, black and silver.  Alternatively, 
	one can set $nav_bg_1, $nav_bg_2, $nav_bg_3 and $nav_bg_4 to provide gradient and
	hover effects.[/li]
[li] banner_colour
	The font colour of the banner element.  Accepts an RGB or Hex value.[/li]
[li] bgcolour
	Set the body background colour.  Accepts an RGB or Hex value.[/li]
[li] background_image
	Sets a background image.  Accepts a URL or path.[/li]
[li] item_colour
	Set the background colour of items.  Accepts an RGB or Hex value.[/li]
[li] item_opacity
	Set the opacity of items.  Accepts a value from 0.01 to 1[/li]
[li] toolicon_colour
	Set the colour of tool icons.  Accepts an RGB or Hex value.[/li]
[li] toolicon_activecolour
	Set the colour of active or hovered icon tools.[/li]
[li] font_size
	Set the size of fonts in items and posts.  Accepts px or em.[/li]
[li] body_font_size
	Sets the size of fonts at the body level.  Accepts px or em.[/li]
[li] font_colour
	Sets the font colour.  Accepts an RGB or Hex value.[/li]
[li] radius
	Set the radius of corners.  Accepts a numeral, and is always in px.[/li]
[li] shadow
	Set the size of shadows shown with inline images.  Accepts a numerical 
	value.  Note shadows are not applied to smileys.[/li]
[li] converse_width
	Set the maximum width of conversations.  Accepts px, or %.[/li]
[li] nav_min_opacity[/li]
[li] top_photo[/li]
[li] reply_photo[/li]
[li] sloppy_photos
	Determins whether photos are &quot;sloppy&quot; or aligned.  Set or unset (1 or '')[/li]