{#<div class="wall-item-outside-wrapper$indent wallwall" id="wall-item-outside-wrapper-$id" >
	<div class="wall-item-content-wrapper$indent" id="wall-item-content-wrapper-$id" >
		<div class="wall-item-info wallwall" id="wall-item-info-$id">
			<div class="wall-item-photo-wrapper mframe wwto" id="wall-item-ownerphoto-wrapper-$id" >
				<a href="$owner_url" target="redir" title="$olinktitle" class="wall-item-photo-link" id="wall-item-ownerphoto-link-$id">
				<img src="$owner_photo" class="wall-item-photo$osparkle" id="wall-item-ownerphoto-$id" style="height: 80px; width: 80px;" alt="$owner_name" /></a>
			</div>
			<div class="wall-item-arrowphoto-wrapper" ><img src="images/larrow.gif" alt="$wall" /></div>
			<div class="wall-item-photo-wrapper mframe wwfrom" id="wall-item-photo-wrapper-$id" 
				onmouseover="if (typeof t$id != 'undefined') clearTimeout(t$id); openMenu('wall-item-photo-menu-button-$id')"
                onmouseout="t$id=setTimeout('closeMenu(\'wall-item-photo-menu-button-$id\'); closeMenu(\'wall-item-photo-menu-$id\');',200)">
				<a href="$profile_url" target="redir" title="$linktitle" class="wall-item-photo-link" id="wall-item-photo-link-$id">
				<img src="$thumb" class="wall-item-photo$sparkle" id="wall-item-photo-$id" style="height: 80px; width: 80px;" alt="$name" /></a>
				<span onclick="openClose('wall-item-photo-menu-$id');" class="fakelink wall-item-photo-menu-button" id="wall-item-photo-menu-button-$id">menu</span>
                <div class="wall-item-photo-menu" id="wall-item-photo-menu-$id">
                    <ul>
                        $item_photo_menu
                    </ul>
                </div>

			</div>
			<div class="wall-item-photo-end"></div>
			<div class="wall-item-wrapper" id="wall-item-wrapper-$id" >
				$lock
				<div class="wall-item-location" id="wall-item-location-$id">$location</div>
			</div>
		</div>
		<div class="wall-item-author">
				<a href="$profile_url" target="redir" title="$linktitle" class="wall-item-name-link"><span class="wall-item-name$sparkle" id="wall-item-name-$id" >$name</span></a> $to <a href="$owner_url" target="redir" title="$olinktitle" class="wall-item-name-link"><span class="wall-item-name$osparkle" id="wall-item-ownername-$id">$owner_name</span></a> $vwall<br />
				<div class="wall-item-ago"  id="wall-item-ago-$id">$ago</div>				
		</div>			
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
	</div>	
	<div class="wall-item-wrapper-end"></div>
	<div class="wall-item-like" id="wall-item-like-$id">$like</div>
	<div class="wall-item-dislike" id="wall-item-dislike-$id">$dislike</div>
	<div class="wall-item-comment-separator"></div>
	<div class="wall-item-comment-wrapper" >
	$comment
	</div>

<div class="wall-item-outside-wrapper-end$indent" ></div>
</div> #}

{{ if $indent }}{{ else }}
<div class="wall-item-decor">
	<span class="icon s22 star $isstarred" id="starred-$id" title="$star.starred">$star.starred</span>
	{{ if $lock }}<span class="icon s22 lock fakelink" onclick="lockview(event,$id);" title="$lock">$lock</span>{{ endif }}	
	<img id="like-rotator-$id" class="like-rotator" src="images/rotator.gif" alt="$wait" title="$wait" style="display: none;" />
</div>
{{ endif }}
<div class="wall-item-container $indent">
	<div class="wall-item-item">
		<div class="wall-item-info">
			<div class="wall-item-photo-wrapper wwto" id="wall-item-ownerphoto-wrapper-$id" >
				<a href="$owner_url" target="redir" title="$olinktitle" class="wall-item-photo-link" id="wall-item-ownerphoto-link-$id">
					<img src="$owner_photo" class="wall-item-photo$osparkle" id="wall-item-ownerphoto-$id" alt="$owner_name" />
				</a>
			</div>
			<div class="wall-item-photo-wrapper wwfrom"
				onmouseover="if (typeof t$id != 'undefined') clearTimeout(t$id); openMenu('wall-item-photo-menu-button-$id')" 
				onmouseout="t$id=setTimeout('closeMenu(\'wall-item-photo-menu-button-$id\'); closeMenu(\'wall-item-photo-menu-$id\');',200)">
				<a href="$profile_url" target="redir" title="$linktitle" class="wall-item-photo-link" id="wall-item-photo-link-$id">
					<img src="$thumb" class="wall-item-photo$sparkle" id="wall-item-photo-$id" alt="$name" />
				</a>
				<a href="#" rel="#wall-item-photo-menu-$id" class="fakelink wall-item-photo-menu-button icon s16 menu" id="wall-item-photo-menu-button-$id">menu</a>
				<ul class="wall-item-menu menu-popup" id="wall-item-photo-menu-$id">
				$item_photo_menu
				</ul>
				
			</div>	
			<div class="wall-item-location">$location</div>	
		</div>
		<div class="wall-item-content">
			{{ if $title }}<h2><a href="$plink.href">$title</a></h2>{{ endif }}
			$body
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links">
			{{ if $plink }}<a class="icon s16 link" title="$plink.title" href="$plink.href">$plink.title</a>{{ endif }}
		</div>
		<div class="wall-item-actions">
			<div class="wall-item-actions-author">
				<a href="$profile_url" target="redir" title="$linktitle" class="wall-item-name-link"><span class="wall-item-name$sparkle">$name</span></a> <span class="wall-item-ago">$ago</span>
				 <br/>$to <a href="$owner_url" target="redir" title="$olinktitle" class="wall-item-name-link"><span class="wall-item-name$osparkle" id="wall-item-ownername-$id">$owner_name</span></a> $vwall
				 
			</div>
			
			<div class="wall-item-actions-social">
			{{ if $star }}
				<a href="#" id="star-$id" onclick="dostar($id); return false;"  class="$star.classdo"  title="$star.do">$star.do</a>
				<a href="#" id="unstar-$id" onclick="dostar($id); return false;"  class="$star.classundo"  title="$star.undo">$star.undo</a>
			{{ endif }}
			
			{{ if $vote }}
				<a href="#" id="like-$id" title="$vote.like.0" onclick="dolike($id,'like'); return false">$vote.like.1</a>
				<a href="#" id="dislike-$id" title="$vote.dislike.0" onclick="dolike($id,'dislike'); return false">$vote.dislike.1</a>
			{{ endif }}
						
			{{ if $vote.share }}
				<a href="#" id="share-$id" title="$vote.share.0" onclick="jotShare($id); return false">$vote.share.1</a>
			{{ endif }}			
			</div>
			
			<div class="wall-item-actions-tools">

				{{ if $drop.dropping }}
					<input type="checkbox" title="$drop.select" name="itemselected[]" value="$id" />
					<a href="item/drop/$id" onclick="return confirmDelete();" class="icon delete s16" title="$drop.delete">$drop.delete</a>
				{{ endif }}
				{{ if $edpost }}
					<a class="icon edit s16" href="$edpost.0" title="$edpost.1"></a>
				{{ endif }}
			</div>
			
		</div>
	</div>
</div>
