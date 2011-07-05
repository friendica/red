<div class="settings-block">
	<h3 class="settings-heading">$title</h3>
	<div class='field noedit'>
		<label>$label</label>
		<tt>$key</tt>
	</div>
	
	<div class="settings-submit-wrapper">
		<input type="submit" value="$submit" class="settings-submit" name="widgets-submit" />
	</div>
	
	<h4>$widgets_h</h4>
	<ul>
		{{ for $widgets as $w }}
			<li><a href="$baseurl/widgets/$w.0/?k=$key&p=1">$w.1</a></li>
		{{ endfor }}
	</ul>
	
</div>
