

function cmtBbOpen(comment, id) {
	if($(comment).hasClass('comment-edit-text-full')) {
		$(".comment-edit-bb-" + id).show();
		return true;
	}
	return false;
}

function cmtBbClose(comment, id) {
//	if($(comment).hasClass('comment-edit-text-empty')) {
//		$(".comment-edit-bb-" + id).hide();
//		return true;
//	}
	return false;
}

$(document).ready(function() {

$('.group-edit-icon').hover(
	function() {
		$(this).css('opacity','1.0');},
	function() {
		$(this).css('opacity','0');}
);

$('.sidebar-group-element').hover(
	function() {
		id = $(this).attr('id');
		$('#edit-' + id).css('opacity','1.0');},

	function() {
		id = $(this).attr('id');
		$('#edit-' + id).css('opacity','0');}
	);


$('.savedsearchdrop').hover(
	function() {
		$(this).css('opacity','1.0');},
	function() {
		$(this).css('opacity','0');}
	);

$('.savedsearchterm').hover(
	function() {
		id = $(this).attr('id');
		$('#dropicon-' + id).css('opacity','1.0');},

	function() {
		id = $(this).attr('id');
		$('#dropicon-' + id).css('opacity','0');
	});

});


$(document).ready(function(){
	var doctitle = document.title;
	function checkNotify() {
		var notifyUpdateElem = document.getElementById('notify-update');
		if(notifyUpdateElem !== null) { 
	        if(notifyUpdateElem.innerHTML != "")
    		    document.title = "("+notifyUpdateElem.innerHTML+") " + doctitle;
	        else
    		    document.title = doctitle;
		}
	};
	setInterval(function () {checkNotify();}, 10 * 1000);
});