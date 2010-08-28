<!DOCTYPE html ><?php // This is a perfect example of why I prefer to use template files rather than mixed PHP/HTML ?>
<html>
<head>
  <title><?php echo $page['title']; ?></title>
  <?php echo $page['htmlhead']; ?>
</head>
<body>
<header><?php echo $page['header']; ?></header>
<nav><?php echo $page['nav']; ?></nav>
<aside>
<?php if((is_array($profile)) && count($profile)) { ?>
<div class="vcard">
	<?php if(strlen($profile['name'])) { ?>
		<div class="fn"><?php echo $profile['name']; ?></div>
	<?php } ?>

	<?php if(strlen($profile['photo'])) { ?>
		<div id="profile-photo-wrapper"><img class="photo" src="<?php echo $profile['photo']; ?>" alt="<?php echo $profile['name']; ?>" /></div>
	<?php } ?>

	<div id="profile-extra-links">
	<ul>
	<?php if($profile['uid'] != $_SESSION['uid']) { ?>
	<li><a id="dfrn-request-link" href="dfrn_request/<?php echo $profile['nickname']; ?>">Introductions</a></li>
	<?php } ?>
	<?php if(! $profile['hide-friends']) echo '<li><a id="viewcontacts-link" href="viewcontacts/' . $profile['nickname'] . '">View Contacts</a></li>'; ?>

	</ul>

	</div>

	<?php if ( (strlen($profile['address'])) 
		|| (strlen($profile['locality']))
		|| (strlen($profile['region'])) 
		|| (strlen($profile['postal-code'])) 
		|| (strlen($profile['country-name']))) { ?>
		<div class="location">Location:
			<div class="adr">
				<div class="street-address"><?php if(strlen($profile['address'])) echo $profile['address']; ?></div>
				<span class="city-state-zip"><span class="locality"><?php echo $profile['locality']; ?></span><?php if(strlen($profile['locality'])) echo ', '; ?><span class="region"><?php echo $profile['region'] ?></span><?php if(strlen($profile['postal-code'])) { ?> <span class="postal-code"><?php echo $profile['postal-code']; ?></span><?php } ?></span>
				<span class="country-name"><?php echo $profile['country-name']; ?></span>
			</div>
		</div>

	<?php } ?>

	<?php if(strlen($profile['gender'])) { ?>
		<div class="mf">Gender: <span class="x-gender"><?php echo $profile['gender']; ?></span></div>

	<?php } ?>

	<?php if(strlen($profile['pubkey'])) { ?>
		<div class="key" style="display: none"><?php echo $profile['pubkey']; ?></div>
	<?php } ?>
</div>
<?php } ?>
<?php if(strlen($profile['marital'])) { ?>
<div class="marital"><span class="marital-label"><span class="heart">&hearts;</span> Status: </span><span class="marital-text"><?php echo $profile['marital']; ?></span></div>
<?php } ?>
<?php if(strlen($profile['url'])) { ?>
<div class="homepage"><span class="homepage-label">Homepage: </span><span class="homepage-url"><?php echo $profile['homepage']; ?></span></div>
<?php } ?>
<?php echo $page['aside'] ?>
</aside>
<section>
<?php echo $page['content']; ?>
</section>
<footer>
<?php echo $page['footer']; ?>
</footer>
</body>
</html>

