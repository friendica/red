<!DOCTYPE html>
<html>
<head>
  <title><?php if(x($page,'title')) echo $page['title'] ?></title>
  <script>var baseurl="<?php echo $a->get_baseurl() ?>";</script>
  <?php if(x($page,'htmlhead')) echo $page['htmlhead'] ?>
</head>
<body>
	<nav><?php if(x($page,'nav')) echo $page['nav']; ?></nav>
	<section><?php if(x($page,'content')) echo $page['content']; ?>
		<div id="page-footer"></div>
	</section>
</body>
</html>
