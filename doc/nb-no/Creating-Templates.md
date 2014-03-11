Creating Page Templates
=======================


A page template for use with Comanche requires two files - a PHP template and a CSS file. Page templates will need to be installed by the system administrator of your site. 


First choose a name. Here we'll create a template and call it "demo".

You will need to create the files "view/php/demo.php" and "view/css/demo.css" to hold the PHP template and CSS respectively. 

To get a better idea of this process, let's look at an existing template - the "default" template. This is used by default throughout the application. 

view/php/default.php
====================

	<!DOCTYPE html >
	<html>
	<head>
		<title><?php if(x($page,'title')) echo $page['title'] ?></title>
		<script>var baseurl="<?php echo $a->get_baseurl() ?>";</script>
		<?php if(x($page,'htmlhead')) echo $page['htmlhead'] ?>
	</head>
	<body>
		<?php if(x($page,'nav')) echo $page['nav']; ?>
		<aside id="region_1"><?php if(x($page,'aside')) echo $page['aside']; ?></aside>
		<section id="region_2"><?php if(x($page,'content')) echo $page['content']; ?>
			<div id="page-footer"></div>
        	<div id="pause"></div>
		</section>
	<aside id="region_3"><?php if(x($page,'right_aside')) echo $page['right_aside']; ?></aside>      
	<footer><?php if(x($page,'footer')) echo $page['footer']; ?></footer>
	</body>
	</html>    


Here's is the corresponding CSS file

view/php/default.css
====================


	aside#region_1 {
		display: block;
		width: 210px;
		position: absolute;
		top: 65px;
		left: 0;
		margin-left: 10px;
	}

	aside input[type='text'] {
 		width: 174px;
	}


	section {
		position: absolute;
		top: 65px;
		left: 250px;
		display: block;
		right: 15px;
		padding-bottom: 350px;
	}


Some things you may notice when looking at these definitions:

* We have not specified any CSS for the "nav", "right_aside", or "footer" regions. In this template "nav" and "footer" will be the full page width and we will let the size and placement of these elements be controlled by the theme. "right_aside" is not currently used. 

* There are elements on the page such as "page-footer" and "pause" for which there is no apparent content. This content will come from Javascript elements.  

* Our default template uses absolute positioning. Modern web design often uses "float" div containers so that scrollbars aren't typically needed when viewing on small-screen devices. 

To design a new template, it is best to start with an existing template, and modify it as desired. That is what we will do here. 

The way that Comanche provides content inside a specific region is by using a region tag.

	[region=aside][widget=profile][/widget][/region]

This example will place a "profile" widget in the "aside" region. But what it actually does is place the HTML for the widget into a code variable **$page['aside']**. Our default page template defines a region on the page (the CSS positions this as an absolute sidebar) and then inserts the contents of $page['aside'] (if it exists). 

So if you wanted to create a template with a region named "foo", you would provide a place for it on the page, then include the contents of $page['foo'] wherever you wanted to use it, and then using Comanche, you could specify 

	[region=foo][widget=profile][/widget][/region]

and this would place a profile widget into the "foo" region you created. 

Use the CSS file to position the region on the page where desired and optionally control its size.

[To be continued] 