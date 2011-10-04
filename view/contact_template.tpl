
<div class="contact-entry-wrapper" id="contact-entry-wrapper-$id" >
	<div class="contact-entry-photo-wrapper" >
		<div class="contact-entry-photo mframe" id="contact-entry-photo-$id"
		onmouseover="if (typeof t$id != 'undefined') clearTimeout(t$id); openMenu('contact-photo-menu-button-$id')" onmouseout="t$id=setTimeout('closeMenu(\'contact-photo-menu-button-$id\'); closeMenu(\'contact-photo-menu-$id\');',200)" >

			<a href="$url" title="$img_hover" /><img src="$thumb" $sparkle alt="$name" /></a>

			<span onclick="openClose('contact-photo-menu-$id');" class="fakelink contact-photo-menu-button" id="contact-photo-menu-button-$id">menu</span>
                <div class="contact-photo-menu" id="contact-photo-menu-$id">
                    <ul>
                        $contact_photo_menu
                    </ul>
                </div>

		</div>
			
	</div>
	<div class="contact-entry-photo-end" ></div>
		<div class="contact-entry-name" id="contact-entry-name-$id" >$name</div>

	<div class="contact-entry-end" ></div>
</div>
