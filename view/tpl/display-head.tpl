<script>
$(document).ready(function() {
	$(".comment-edit-wrapper textarea").contact_autocomplete(baseurl+"/acl?f=&n=1");
	// make auto-complete work in more places
	$(".wall-item-comment-wrapper textarea").contact_autocomplete(baseurl+"/acl?f=&n=1");
});
</script>

