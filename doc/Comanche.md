Comanche Page Description Language
==================================


Comanche is a markup language similar to bbcode with which to create elaborate and complex web pages by assembling them from a series of components - some of which are pre-built and others which can be defined on the fly. Comanche uses a Page Decription Language to create these pages.

Comanche primarily chooses what content will appear in various regions of the page. The various regions have names and these names can change depending on what layout template you choose.

Currently there are three layout templates, unless your site provides additional layouts. 

	default

	The default template defines a "nav" region across the top, "aside" as a fixed width sidebar, 
	"content" for the main content region, and "footer" for a page footer.


	full

	The full template defines the same as the default template with the exception that there is no "aside" region.


	choklet

	The choklet template provides a number of fluid layout styles which can be specified by flavour:

			(default flavour) - a two column layout similar to the "default" template, but more fluid
			three - three column layout (adds a "right_aside" region to the default template)
			edgestwo - two column layout with fixed side margins
			edgesthree - three column layout with fixed side margins
			full - three column layout with fixed side margins and adds a "header" region beneath the navigation bar


To choose a layout template, use the "template" tag.

	[template]full[/template]

To choose the "choklet" template with the "three" flavour:

	[template=three]choklet[/template]


The default template will be used if no other template is specified. The template can use any names it desires for content regions. You will be using 'region' tags to decide what content to place in the respective regions.


Two "macros" have been defined for your use.

	$nav - replaced with the site navigation bar content.
	$content - replaced with the main page content.


By default, $nav is placed in the "nav" page region and $content is placed in the "content" region. You only need to use these macros if you wish to re-arrange where these items appear, either to change the order or to move them to other regions.


To select a theme for your page, use the 'theme' tag.

	[theme]apw[/theme]

This will select the theme named "apw". By default your channel's preferred theme will be used.

	[theme=dark]redbasic[/theme]

This will select the theme named "redbasic" and load the "dark" theme schema for this theme. 

**Regions**

Each region has a name, as noted above. You will specify the region of interest using a 'region' tag, which includes the name. Any content you wish placed in this region should be placed between the opening region tag and the closing tag.

	[region=aside]....content goes here....[/region]
	[region=nav]....content goes here....[/region]



**Menus and Blocks**

Your webpage creation tools allow you to create menus and blocks, in addition to page content. These provide a chunk of existing content to be placed in whatever regions and whatever order you specify. Each of these has a name which you define when the menu or block is created.

	[menu]mymenu[/menu]

This places the menu called "mymenu" at this location on the page, which must be inside a region. 

	[menu=horizontal-menu]mymenu[/menu]

This places the menu called "mymenu" at this location on the page, which must be inside a region. Additionally it adds the CSS class "horizontal-menu" to this menu. This *may* result in a menu that looks different than the default menu style, *if* the css for the current theme defines a "horizontal-menu" class. 


	[block]contributors[/block]

This places a block named "contributors" in this region.


**Widgets**

Widgets are executable apps provided by the system which you can place on your page. Some widgets take arguments which allows you to tailor the widget to your purpose. (TODO: list available widgets and arguments). The base system provides

	profile - widget which duplicates the profile sidebar of your channel page. This widget takes no arguments
	tagcloud - provides a tag cloud of categories
		count - maximum number of category tags to list	



Widgets and arguments are specified with the 'widget' and 'var' tags.

	[widget=recent_visitors][var=count]24[/var][/widget]

This loads the "recent_visitors" widget and supplies it with the argument "count" set to "24". 
 

**Comments**

The 'comment' tag is used to delimit comments. These comments will not appear on the rendered page.

	[comment]This is a comment[/comment] 
	
 

**Complex Example**

Please note that pasting this example into a layout page is not likely to do anything useful as the chosen names (template, theme, regions, etc.) may not correspond to any existing webpage components.  

	[comment]use an existing page template which provides a banner region plus 3 columns beneath it[/comment]

	[template]3-column-with-header[/template]

	[comment]Use the "darknight" theme[/comment]

	[theme]darkknight[/theme]

	[comment]Use the existing site navigation menu[/comment]

	[region=nav]$nav[/region]

	[region=side]

		[comment]Use my chosen menu and a couple of widgets[/comment]

		[menu]myfavouritemenu[/menu]

		[widget=recent_visitors]
			[var=count]24[/var]
			[var=names_only]1[/var]
		[/widget]

		[widget=tagcloud][/widget]
		[block]donate[/block]

	[/region]



	[region=middle]

		[comment]Show the normal page content[/comment]

		$content

	[/region]



	[region=right]

		[comment]Show my condensed channel "wall" feed and allow interaction if the observer is allowed to interact[/comment]

		[widget]channel[/widget]

	[/region]


