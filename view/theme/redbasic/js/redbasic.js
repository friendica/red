

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

document.jotpermsunlock = 'icon-unlock';
document.jotpermslock = 'icon-lock';

if($('#jot-perms-icon').hasClass('lock'))
	$('#jot-perms-icon').addClass('icon-lock');
if($('#jot-perms-icon').hasClass('unlock'))
	$('#jot-perms-icon').addClass('icon-unlock');

$('.group-edit-icon').hover(
	function() {
		$(this).addClass('icon'); $(this).removeClass('iconspacer');},
	function() {
		$(this).removeClass('icon'); $(this).addClass('iconspacer');}
	);

$('.sidebar-group-element').hover(
	function() {
		id = $(this).attr('id');
		$('#edit-' + id).addClass('icon'); $('#edit-' + id).removeClass('iconspacer');},

	function() {
		id = $(this).attr('id');
		$('#edit-' + id).removeClass('icon');$('#edit-' + id).addClass('iconspacer');}
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
