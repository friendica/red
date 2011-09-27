{{ if $indent }}{{ else }}
<div class="wall-item-decor">
	<span class="icon s22 star $isstarred" id="starred-$id" title="$star.starred">$star.starred</span>
	{{ if $lock }}<span class="icon s22 lock fakelink" onclick="lockview(event,$id);" title="$lock">$lock</span>{{ endif }}	
</div>
{{ endif }}
<div class="wall-item-container $indent">
	<div class="wall-item-item">
		<div class="wall-item-info">
			<div class="wall-item-photo-wrapper"
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
			<a href="$profile_url" target="redir" title="$linktitle" class="wall-item-name-link"><span class="wall-item-name$sparkle">$name</span></a> <span class="wall-item-ago">$ago</span>
			
			{{ if $star }}
			<a href="#" id="star-$id" onclick="dostar($id); return false;"  class="$star.classdo"  title="$star.do">$star.do</a>
			<a href="#" id="unstar-$id" onclick="dostar($id); return false;"  class="$star.classundo"  title="$star.undo">$star.undo</a>
			{{ endif }}
		</div>
	</div>
</div>

