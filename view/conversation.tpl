{{ for $threads as $thread }}
<div id="tread-wrapper-$thread.id" class="tread-wrapper">
	$thread.html
</div>
{{ endfor }}

{{ if $dropping }}
<a href="#" onclick="deleteCheckedItems();return false;">
	<span class="icon s22 delete text">$dropping</span>
</a>
{{ endif }}
