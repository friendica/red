<!-- test -->
<div class="wall-item-outside-wrapper$indent" id="wall-item-outside-wrapper-$id" >
	<div class="wall-item-content-wrapper$indent" id="wall-item-content-wrapper-$id" >
		<div class="wall-item-info" id="wall-item-info-$id">
			<div class="wall-item-photo-wrapper" id="wall-item-photo-wrapper-$id" 
				 onmouseover="if (typeof t$id != 'undefined') clearTimeout(t$id); openMenu('wall-item-photo-menu-button-$id')" 
				 onmouseout="t$id=setTimeout('closeMenu(\'wall-item-photo-menu-button-$id\'); closeMenu(\'wall-item-photo-menu-$id\');',200)">
				<a href="$profile_url" title="$linktitle" class="wall-item-photo-link" id="wall-item-photo-link-$id">
					<img src="$thumb" class="wall-item-photo$sparkle" id="wall-item-photo-$id" style="height: 80px; width: 80px;" alt="$name" />
				</a>
				<span onclick="openClose('wall-item-photo-menu-$id');" class="fakelink wall-item-photo-menu-button" id="wall-item-photo-menu-button-$id">menu</span>
				<div class="wall-item-photo-menu" id="wall-item-photo-menu-$id">
					<ul>
						$item_photo_menu
					</ul>
				</div>
			</div>
			<div class="wall-item-photo-end"></div>
			<div class="wall-item-location" id="wall-item-location-$id">{{ if $location }}<span class="icon globe"></span>$location {{ endif }}</div>				
		</div>
		<div class="wall-item-lock-wrapper">$lock</div>
		<div class="wall-item-content" id="wall-item-content-$id" >
			<div class="wall-item-title" id="wall-item-title-$id">$title</div>
			<div class="wall-item-title-end"></div>
			<div class="wall-item-body" id="wall-item-body-$id" >$body</div>
		</div>

		<div class="wall-item-tools" id="wall-item-tools-$id">
			$vote
			$plink
			$edpost
			$star
			$drop
		</div>
		
		<div class="wall-item-author">
				<a href="$profile_url" title="$linktitle" class="wall-item-name-link"><span class="wall-item-name$sparkle" id="wall-item-name-$id" >$name</span></a>
				<div class="wall-item-ago"  id="wall-item-ago-$id">$ago</div>
				
		</div>	
	</div>
	<div class="wall-item-wrapper-end"></div>
	<div class="wall-item-like" id="wall-item-like-$id">$like</div>
	<div class="wall-item-dislike" id="wall-item-dislike-$id">$dislike</div>
	<div class="wall-item-comment-wrapper" >
	$comment
	</div>
</div>

<div class="wall-item-outside-wrapper-end$indent" ></div>

