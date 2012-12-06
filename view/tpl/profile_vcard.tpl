<div class="vcard">

	{{ if $profile.edit }}
	<div class="action">
	<a class="profile-edit-side-link icon edit" rel="#profiles-menu" title="$profile.edit.3" href="#" ><span>$profile.edit.1</span></a>
	<ul id="profiles-menu" class="menu-popup">
		{{ for $profile.menu.entries as $e }}
		<li>
			<a href="profiles/$e.id"><img src='$e.photo'>$e.profile_name</a>
		</li>
		{{ endfor }}
		<li><a href="profile_photo" >$profile.menu.chg_photo</a></li>
		<li><a href="profiles/new" id="profile-listing-new-link">$profile.menu.cr_new</a></li>
				
	</ul>
	</div>
	{{ endif }}

	<div class="fn label">$profile.name</div>
	
				
	
	{{ if $pdesc }}<div class="title">$profile.pdesc</div>{{ endif }}
	<div id="profile-photo-wrapper"><img class="photo" width="175" height="175" src="$profile.photo?rev=$profile.picdate" alt="$profile.name"></div>



	{{ if $location }}
		<dl class="location"><dt class="location-label">$location</dt> 
		<dd class="adr">
			{{ if $profile.address }}<div class="street-address">$profile.address</div>{{ endif }}
			<span class="city-state-zip">
				<span class="locality">$profile.locality</span>{{ if $profile.locality }}, {{ endif }}
				<span class="region">$profile.region</span>
				<span class="postal-code">$profile.postal_code</span>
			</span>
			{{ if $profile.country_name }}<span class="country-name">$profile.country_name</span>{{ endif }}
		</dd>
		</dl>
	{{ endif }}

	{{ if $gender }}<dl class="mf"><dt class="gender-label">$gender</dt> <dd class="x-gender">$profile.gender</dd></dl>{{ endif }}
	

	{{ if $marital }}<dl class="marital"><dt class="marital-label"><span class="heart">&hearts;</span>$marital</dt><dd class="marital-text">$profile.marital</dd></dl>{{ endif }}

	{{ if $homepage }}<dl class="homepage"><dt class="homepage-label">$homepage</dt><dd class="homepage-url"><a href="$profile.homepage" >$profile.homepage</a></dd></dl>{{ endif }}

	
	<div id="profile-extra-links">
		<ul>
			{{ if $connect }}
				<li><a id="dfrn-request-link" href="dfrn_request/$profile.nickname">$connect</a></li>
			{{ endif }}
			{{ if $wallmessage }}
				<li><a id="wallmessage-link" href="wallmessage/$profile.nickname">$wallmessage</a></li>
			{{ endif }}
		</ul>
	</div>
</div>

$contact_block


