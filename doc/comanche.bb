[b]Comanche Page Description Language[/b]

Comanche is a markup language similar to bbcode with which to create elaborate and complex web pages by assembling them from a series of components - some of which are pre-built and others which can be defined on the fly. Comanche uses a Page Decription Language to create these pages.

Comanche primarily chooses what content will appear in various regions of the page. The various regions have names and these names can change depending on what layout template you choose.

Currently there are two layout templates, unless your site provides additional layouts (TODO list all templates)

[code]
	default

	The default template defines a &quot;nav&quot; region across the top, &quot;aside&quot; as a fixed width sidebar, 
	&quot;content&quot; for the main content region, and &quot;footer&quot; for a page footer.


	full

	The full template defines the same as the default template with the exception that there is no &quot;aside&quot; region.
[/code]

To choose a layout template, use the 'layout' tag.

[code]
	[layout]full[/layout]
[/code]

The default template will be used if no other template is specified. The template can use any names it desires for content regions. You will be using 'region' tags to decide what content to place in the respective regions.


Two &quot;macros&quot; have been defined for your use.
[code]
	$nav - replaced with the site navigation bar content.
	$content - replaced with the main page content.
[/code]

By default, $nav is placed in the &quot;nav&quot; page region and $content is placed in the &quot;content&quot; region. You only need to use these macros if you wish to re-arrange where these items appear, either to change the order or to move them to other regions.


To select a theme for your page, use the 'theme' tag.
[code]
	[theme]apw[/theme]
[/code]
This will select the theme named &quot;apw&quot;. By default your channel's preferred theme will be used.


[b]Regions[/b]

Each region has a name, as noted above. You will specify the region of interest using a 'region' tag, which includes the name. Any content you wish placed in this region should be placed between the opening region tag and the closing tag.

[code]
	[region=aside]....content goes here....[/region]
	[region=nav]....content goes here....[/region]
[/code]


[b]Menus and Blocks[/b]

Your webpage creation tools allow you to create menus and blocks, in addition to page content. These provide a chunk of existing content to be placed in whatever regions and whatever order you specify. Each of these has a name which you define when the menu or block is created.
[code]
	[menu]mymenu[/menu]
[/code]
This places the menu called &quot;mymenu&quot; at this location on the page, which must be inside a region. 
[code]
	[block]contributors[/block]
[/code]
This places a block named &quot;contributors&quot; in this region.


[b]Widgets[/b]

Widgets are executable apps provided by the system which you can place on your page. Some widgets take arguments which allows you to tailor the widget to your purpose. (TODO: list available widgets and arguments). The base system provides
[code]
	profile - widget which duplicates the profile sidebar of your channel page. This widget takes no arguments
	tagcloud - provides a tag cloud of categories
		count - maximum number of category tags to list	
[/code]


Widgets and arguments are specified with the 'widget' and 'arg' tags.
[code]
	[widget=recent_visitors][arg=count]24[/arg][/widget]
[/code]

This loads the &quot;recent_visitors&quot; widget and supplies it with the argument &quot;count&quot; set to &quot;24&quot;. 
 

[b]Comments[/b]

The 'comment' tag is used to delimit comments. These comments will not appear on the rendered page.

[code]
	[comment]This is a comment[/comment] 
[/code]
 

[b]Complex Example[/b]

[code]
	[comment]use an existing page template which provides a banner region plus 3 columns beneath it[/comment]

	[layout]3-column-with-header[/layout]

	[comment]Use the &quot;darknight&quot; theme[/comment]

	[theme]darkknight[/theme]

	[comment]Use the existing site navigation menu[/comment]

	[region=nav]$nav[/region]

	[region=side]

		[comment]Use my chosen menu and a couple of widgets[/comment]

		[menu]myfavouritemenu[/menu]

		[widget=recent_visitors]
			[arg=count]24[/arg]
			[arg=names_only]1[/arg]
		[/widget]

		[widget=tagcloud][/widget]
		[block]donate[/block]

	[/region]



	[region=middle]

		[comment]Show the normal page content[/comment]

		$content

	[/region]



	[region=right]

		[comment]Show my condensed channel &quot;wall&quot; feed and allow interaction if the observer is allowed to interact[/comment]

		[widget]channel[/widget]

	[/region]
[/code]