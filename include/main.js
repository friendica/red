
  function openClose(theID) {
    if(document.getElementById(theID).style.display == "block") { 
      document.getElementById(theID).style.display = "none" 
    }
    else { 
      document.getElementById(theID).style.display = "block" 
    } 
  }

  function openMenu(theID) {
      document.getElementById(theID).style.display = "block" 
  }

  function closeMenu(theID) {
      document.getElementById(theID).style.display = "none" 
  }

	function commentOpen(obj,id) {
		if(obj.value == 'Comment') {
			obj.value = '';
			obj.className = "comment-edit-text-full";
			openMenu("comment-edit-submit-wrapper-" + id);
		}
	}
	function commentClose(obj,id) {
		if(obj.value == '') {
			obj.value = 'Comment';
			obj.className="comment-edit-text-empty";
			closeMenu("comment-edit-submit-wrapper-" + id);
		}
	}

	var src = null;
	var prev = null;
	var livetime = null;
	var msie = false;
	var stopped = false;
	var timer = null;
	var pr = 0;

	$(document).ready(function() {
		$.ajaxSetup({cache: false});
		msie = $.browser.msie ;
 		NavUpdate(); 
		// Allow folks to stop the ajax page updates with the pause/break key
		$(document).keypress(function(event) {
			if(event.keyCode == '19') {
				event.preventDefault();
				if(stopped == false) {
					stopped = true;
					$('#pause').html('<img src="images/pause.gif" alt="pause" style="border: 1px solid black;" />');
				}
				else {
					stopped = false;
					$('#pause').html('');
				}
			}
		});					
	});

	function NavUpdate() {

		if($('#live-network').length) { src = 'network'; liveUpdate(); }
		if($('#live-profile').length) { src = 'profile'; liveUpdate(); }

		if(! stopped) {
			$.get("ping",function(data) {
				$(data).find('result').each(function() {
					var net = $(this).find('net').text();
					if(net == 0) { net = ''; }
					$('#net-update').html(net);
					var home = $(this).find('home').text();
					if(home == 0) { home = ''; }
					$('#home-update').html(home);
					var mail = $(this).find('mail').text();
					if(mail == 0) { mail = ''; }
					$('#mail-update').html(mail);
					var intro = $(this).find('intro').text();
					if(intro == 0) { intro = ''; }
					$('#notify-update').html(intro);
				});
			}) ;
		}
		timer = setTimeout(NavUpdate,30000);

	}

	function liveUpdate() {
		if((src == null) || (stopped) || (! profile_uid)) { $('.like-rotator').hide(); return; }
		if($('.comment-edit-text-full').length) {
			livetime = setTimeout(liveUpdate, 10000);
			return;
		}
		prev = 'live-' + src;

		$.get('update_' + src + '?p=' + profile_uid + '&msie=' + ((msie) ? 1 : 0),function(data) {
			$('.wall-item-outside-wrapper',data).each(function() {
				var ident = $(this).attr('id');
				if($('#' + ident).length == 0) { 
                                        $('img',this).each(function() {
                                                $(this).attr('src',$(this).attr('dst'));
                                        });
					$('#' + prev).after($(this));
				}
				else { 

					$('#' + ident + ' ' + '.wall-item-ago').replaceWith($(this).find('.wall-item-ago')); 
					$('#' + ident + ' ' + '.wall-item-comment-wrapper').replaceWith($(this).find('.wall-item-comment-wrapper'));
					$('#' + ident + ' ' + '.wall-item-like').replaceWith($(this).find('.wall-item-like'));
					$('#' + ident + ' ' + '.wall-item-dislike').replaceWith($(this).find('.wall-item-dislike'));
                                        $('#' + ident + ' ' + '.my-comment-photo').each(function() {
                                                $(this).attr('src',$(this).attr('dst'));
                                        });


				}
				prev = ident; 
			});
			$('.like-rotator').hide();
		});

	}

	function confirmDelete() { 
		return confirm("Delete this item?");
	}

	function imgbright(node) {
		$(node).attr("src",$(node).attr("src").replace('hide','show'));
		$(node).css('width',24);
		$(node).css('height',24);
	}

	function imgdull(node) {
		$(node).attr("src",$(node).attr("src").replace('show','hide'));
		$(node).css('width',16);
		$(node).css('height',16);
	}

	// Since ajax is asynchronous, we will give a few seconds for
	// the first ajax call (setting like/dislike), then run the
	// updater to pick up any changes and display on the page.
	// The updater will turn any rotators off when it's done. 
	// This function will have returned long before any of these
	// events have completed and therefore there won't be any
	// visible feedback that anything changed without all this
	// trickery. This still could cause confusion if the "like" ajax call
	// is delayed and NavUpdate runs before it completes.

	function dolike(ident,verb) {
		$('#like-rotator-' + ident.toString()).show();
		$.get('like/' + ident.toString() + '?verb=' + verb );
		if(timer) clearTimeout(timer);
		timer = setTimeout(NavUpdate,3000);
	}
