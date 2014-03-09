<!DOCTYPE html >
<html>
<head>
  <title><?php if(x($page,'title')) echo $page['title'] ?></title>
  <script>var baseurl="<?php echo $a->get_baseurl() ?>";</script>
  <?php if(x($page,'htmlhead')) echo $page['htmlhead'] ?>
</head>
<body>
	<div id="blog-margin">
		<header><?php if(x($page,'header')) echo $page['header']; ?></header>
		<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation"><?php if(x($page,'nav')) echo $page['nav']; ?></nav>
		<div id="blog-banner"><?php if(x($page,'banner')) echo $page['banner']; ?></div>
		<aside id="region_1"><?php if(x($page,'aside')) echo $page['aside']; ?></aside>
		<section id="region_2"><?php if(x($page,'content')) echo $page['content']; ?>
			<div id="page-footer"></div>
			<div id="pause"></div>
		</section>
		<aside id="region_3"><?php if(x($page,'right_aside')) echo $page['right_aside']; ?></aside>
		<div class="clear"></div>
		<footer><?php if(x($page,'footer')) echo $page['footer']; ?></footer>
	</div>
</body>
</html>

