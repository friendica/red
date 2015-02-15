
	function confirmDelete() { return confirm(aStr['delitem']); }

	function commentOpenUI(obj,id) {

		$(document).unbind( "click.commentOpen", handler );

		var handler = function() {
			if(obj.value == aStr['comment']) {
				obj.value = '';
				$("#comment-edit-text-" + id).addClass("comment-edit-text-full").removeClass("comment-edit-text-empty");
				$("#comment-tools-" + id).show();
			}
		};

		$(document).bind( "click.commentOpen", handler );

	}

	function commentCloseUI(obj,id) {

		$(document).unbind( "click.commentClose", handler );

		var handler = function() {
			if(obj.value == '') {
			obj.value = aStr['comment'];
				$("#comment-edit-text-" + id).removeClass("comment-edit-text-full").addClass("comment-edit-text-empty");
				$("#comment-tools-" + id).hide();
			}
		};

		$(document).bind( "click.commentClose", handler );

	}

	function commentOpen(obj,id) {
		if(obj.value == aStr['comment']) {
			obj.value = '';
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			$("#mod-cmnt-wrap-" + id).show();
			openMenu("comment-tools-" + id);
			return true;
		}
		return false;
	}

	function commentClose(obj,id) {
		if(obj.value == '') {
			obj.value = aStr['comment'];
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).addClass("comment-edit-text-empty");
			$("#mod-cmnt-wrap-" + id).hide();
			closeMenu("comment-tools-" + id);
			return true;
		}
		return false;
	}

	function showHideCommentBox(id) {
		if( $('#comment-edit-form-' + id).is(':visible')) {
			$('#comment-edit-form-' + id).hide();
		}
		else {
			$('#comment-edit-form-' + id).show();
		}
	}

	function commentInsert(obj,id) {
		var tmpStr = $("#comment-edit-text-" + id).val();
		if(tmpStr == '$comment') {
			tmpStr = '';
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-tools-" + id);
		}
		var ins = $(obj).html();
		ins = ins.replace('&lt;','<');
		ins = ins.replace('&gt;','>');
		ins = ins.replace('&amp;','&');
		ins = ins.replace('&quot;','"');
		$("#comment-edit-text-" + id).val(tmpStr + ins);
	}


	function insertbbcomment(comment,BBcode,id) {
		// allow themes to override this
		if(typeof(insertFormatting) != 'undefined')
		   return(insertFormatting(comment,BBcode,id));

		var urlprefix = ((BBcode == 'url') ? '#^' : '');

		var tmpStr = $("#comment-edit-text-" + id).val();
		if(tmpStr == comment) {
			tmpStr = "";
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-tools-" + id);
			$("#comment-edit-text-" + id).val(tmpStr);
		}

		textarea = document.getElementById("comment-edit-text-" +id);
		if (document.selection) {
			textarea.focus();
			selected = document.selection.createRange();
			selected.text = urlprefix+"["+BBcode+"]" + selected.text + "[/"+BBcode+"]";
		} else if (textarea.selectionStart || textarea.selectionStart == "0") {
			var start = textarea.selectionStart;
			var end = textarea.selectionEnd;
			textarea.value = textarea.value.substring(0, start) + urlprefix+"["+BBcode+"]" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
		}
		return true;
	}

	function viewsrc(id) {
		$.colorbox({href: 'viewsrc/' + id, maxWidth: '80%', maxHeight: '80%' });
	}

	function qCommentInsert(obj,id) {
		var tmpStr = $("#comment-edit-text-" + id).val();
		if(tmpStr == aStr['comment']) {
			tmpStr = '';
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
		}
		var ins = $(obj).val();
		ins = ins.replace('&lt;','<');
		ins = ins.replace('&gt;','>');
		ins = ins.replace('&amp;','&');
		ins = ins.replace('&quot;','"');
		$("#comment-edit-text-" + id).val(tmpStr + ins);
		$(obj).val('');
	}

	function showHideComments(id) {
		if( $('#collapsed-comments-' + id).is(':visible')) {
			$('#collapsed-comments-' + id + ' .autotime').timeago('dispose');
			$('#collapsed-comments-' + id).slideUp();
			$('#hide-comments-' + id).html(aStr['showmore']);
			$('#hide-comments-total-' + id).show();
		}
		else {
			$('#collapsed-comments-' + id + ' .autotime').timeago();
			$('#collapsed-comments-' + id).slideDown();
			$('#hide-comments-' + id).html(aStr['showfewer']);
			$('#hide-comments-total-' + id).hide();
		}
	}


  function openClose(theID) {
    if(document.getElementById(theID).style.display == "block") { 
      document.getElementById(theID).style.display = "none" 
    }
    else { 
      document.getElementById(theID).style.display = "block" 
    } 
  }

  function closeOpen(theID) {
    if(document.getElementById(theID).style.display == "none") { 
      document.getElementById(theID).style.display = "block" 
    }
    else { 
      document.getElementById(theID).style.display = "none" 
    } 
  }

  function openMenu(theID) {
      document.getElementById(theID).style.display = "block" 
  }

  function closeMenu(theID) {
      document.getElementById(theID).style.display = "none" 
  }

  function markRead(notifType) {
	$.get('ping?f=&markRead='+notifType);
	if(timer) clearTimeout(timer);
	$('#' + notifType + '-update').html('');
	timer = setTimeout(NavUpdate,2000);
  }

  function markItemRead(itemId) {
	$.get('ping?f=&markItemRead='+itemId);
	$('.unseen-wall-indicator-'+itemId).hide();
  }



	var src = null;
	var prev = null;
	var livetime = null;
	var msie = false;
	var stopped = false;
	var totStopped = false;
	var timer = null;
	var pr = 0;
	var liking = 0;
	var in_progress = false;
	var langSelect = false;
	var commentBusy = false;
	var last_popup_menu = null;
	var last_popup_button = null;
	var scroll_next = false;
	var next_page = 1;
	var page_load = true;
	var loadingPage = true;
	var pageHasMoreContent = true;
	var updateCountsOnly = false;
	var divmore_height = 400;
	var last_filestorage_id = null;

	$(function() {
		$.ajaxSetup({cache: false});

		msie = false; // $.browser.msie ;
		
		var e = document.getElementById('content-complete');
		if(e)
			pageHasMoreContent = false;		
		
		/* setup onoff widgets */
		$(".onoff input").each(function(){
			val = $(this).val();
			id = $(this).attr("id");
			$("#"+id+"_onoff ."+ (val==0?"on":"off")).addClass("hidden");
			
		});
		$(".onoff > a").click(function(event){
			event.preventDefault();	
			var input = $(this).siblings("input");
			var val = 1-input.val();
			var id = input.attr("id");
			$("#"+id+"_onoff ."+ (val==0?"on":"off")).addClass("hidden");
			$("#"+id+"_onoff ."+ (val==1?"on":"off")).removeClass("hidden");
			input.val(val);
			//console.log(id);
		});
		
		/* setup field_richtext */
		setupFieldRichtext();


		/* Turn elements with one of our special rel tags into popup menus */
		/* CHANGES: let bootstrap handle popups and only do the loading here */
	
		$('a[rel^=#]').click(function(e){
				manage_popup_menu(this,e);
				return;
		});

		$('span[rel^=#]').click(function(e){
				manage_popup_menu(this,e);
				return;
		});


		function manage_popup_menu(w,e) {
			menu = $( $(w).attr('rel') );

			/* notification menus are loaded dynamically 
			 * - here we find a rel tag to figure out what type of notification to load */

			var loader_source = $(menu).attr('rel');
		
			if(typeof(loader_source) != 'undefined' && loader_source.length) {	
				notify_popup_loader(loader_source);
			}
		}

		// fancyboxes
		// Is this actually used anywhere?
		$("a.popupbox").colorbox({
			'transition' : 'elastic'
		});
		

			
 		NavUpdate(); 
		// Allow folks to stop the ajax page updates with the pause/break key
		$(document).keydown(function(event) {
			if(event.keyCode == '8') {
				var target = event.target || event.srcElement;
				if (!/input|textarea/i.test(target.nodeName)) {
					return false;
				}
			}
			if(event.keyCode == '34') {
				if((pageHasMoreContent) && (! loadingPage)) {
					$('#more').hide();
					$('#no-more').hide();

					next_page++;
					scroll_next = true;
					loadingPage = true;

					if(($('.directory-end').length == 0) && ($('.photos-end').length == 0))
						liveUpdate();
					else
						pageUpdate();
					return true;
				}
			}

			if(event.keyCode == '19' || (event.ctrlKey && event.which == '32')) {
				event.preventDefault();
				if(stopped == false) {
					stopped = true;
					if (event.ctrlKey) {
						totStopped = true;
					}
					$('#pause').html('<img src="images/pause.gif" alt="pause" style="border: 1px solid black;" />');
				} else {
					unpause();
				}
			} else {
				if (!totStopped) {
					unpause();
				}
			}
		});
		
		
	});

	function NavUpdate() {

		if(liking)
			$('.like-rotator').spin(false);

		if(! stopped) {

			var pingCmd = 'ping' + ((localUser != 0) ? '?f=&uid=' + localUser : '');

			$.get(pingCmd,function(data) {

				if(data.invalid == 1) { 
					window.location.href=window.location.href 
				}


				if(! updateCountsOnly) {
					// start live update

					if($('#live-network').length)   { src = 'network'; liveUpdate(); }
					if($('#live-channel').length)   { src = 'channel'; liveUpdate(); }
					if($('#live-home').length)      { src = 'home'; liveUpdate(); }
					if($('#live-display').length)   { src = 'display'; liveUpdate(); }
					if($('#live-search').length)    { src = 'search'; liveUpdate(); }

					if($('#live-photos').length) { 
						if(liking) {
							liking = 0;
							window.location.href=window.location.href 
						}
					}
				}

				updateCountsOnly = false;

				if(data.network == 0) { 
					data.network = ''; 
					$('.net-update').removeClass('show')
				} 
				else { 
					$('.net-update').addClass('show')
				}
				$('.net-update').html(data.network);

				if(data.home == 0) { data.home = ''; $('.home-update').removeClass('show') } else { $('.home-update').addClass('show') }
				$('.home-update').html(data.home);
			

				if(data.intros == 0) { data.intros = ''; $('.intro-update').removeClass('show') } else { $('.intro-update').addClass('show') }
				$('.intro-update').html(data.intros);

				if(data.mail == 0) { data.mail = ''; $('.mail-update').removeClass('show') } else { $('.mail-update').addClass('show') }
				$('.mail-update').html(data.mail);
			

				if(data.notify == 0) { data.notify = ''; $('.notify-update').removeClass('show') } else { $('.notify-update').addClass('show') }
				$('.notify-update').html(data.notify);

				if(data.register == 0) { data.register = ''; $('.register-update').removeClass('show') } else { $('.register-update').addClass('show') }
				$('.register-update').html(data.register);

				if(data.events == 0) { data.events = ''; $('.events-update').removeClass('show') } else { $('.events-update').addClass('show') }
				$('.events-update').html(data.events);

				if(data.events_today == 0) { data.events_today = ''; $('.events-today-update').removeClass('show') } else { $('.events-today-update').addClass('show'); $('.events-update').html(data.events + '*'); }
				$('.events-today-update').html(data.events_today);

				if(data.birthdays == 0) { data.birthdays = ''; $('.birthdays-update').removeClass('show') } else { $('.birthdays-update').addClass('show'); }
				$('.birthdays-update').html(data.birthdays);

				if(data.birthdays_today == 0) { data.birthdays_today = ''; $('.birthdays-today-update').removeClass('show') } else { $('.birthdays-today-update').addClass('show'); $('.birthdays-update').html(data.birthdays + '*'); }
				$('.birthdays-today-update').html(data.birthdays_today);

				if(data.all_events == 0) { data.all_events = ''; $('.all_events-update').removeClass('show') } else { $('.all_events-update').addClass('show') }
				$('.all_events-update').html(data.all_events);
				if(data.all_events_today == 0) { data.all_events_today = ''; $('.all_events-today-update').removeClass('show') } else { $('.all_events-today-update').addClass('show'); $('.all_events-update').html(data.all_events + '*'); }
				$('.all_events-today-update').html(data.all_events_today);

				$.jGrowl.defaults.closerTemplate = '<div>[ ' + aStr['closeAll'] + ']</div>';

				$(data.notice).each(function() {
					$.jGrowl(this.message, { sticky: true, theme: 'notice' });
				});

				$(data.info).each(function(){
					$.jGrowl(this.message, { sticky: false, theme: 'info', life: 10000 });
				});

			

			}) ;
		}
		timer = setTimeout(NavUpdate,updateInterval);
	}



function updatePageItems(mode,data) {


	if(mode === 'append') {
		$(data).each(function() {
			$('#page-end').before($(this));
		});

		if(loadingPage) {
			loadingPage = false;
		}
	}

	var e = document.getElementById('content-complete');
	if(e) {
		pageHasMoreContent = false;		
	}

	collapseHeight();

}


function updateConvItems(mode,data) {

	if(mode === 'update') {
		prev = 'threads-begin';


		$('.thread-wrapper.toplevel_item',data).each(function() {

			var ident = $(this).attr('id');
			// This should probably use the context argument instead
			var commentWrap = $('#'+ident+' .collapsed-comments').attr('id');
			var itmId = 0;
			var isVisible = false;

			if(typeof commentWrap !== 'undefined')
				itmId = commentWrap.replace('collapsed-comments-','');
				
			if($('#' + ident).length == 0 && profile_page == 1) {
				$('img',this).each(function() {
					$(this).attr('src',$(this).attr('dst'));
				});
				if($('#collapsed-comments-'+itmId).is(':visible'))
					isVisible = true;
				$('#' + prev).after($(this));
				if(isVisible)
					showHideComments(itmId);
				$("> .wall-item-outside-wrapper .autotime, > .thread-wrapper .autotime",this).timeago();
			}
			else {
				$('img',this).each(function() {
					$(this).attr('src',$(this).attr('dst'));
				});
				if($('#collapsed-comments-'+itmId).is(':visible'))
					isVisible = true;
				$('#' + ident).replaceWith($(this));
				if(isVisible)
					showHideComments(itmId);
				$("> .wall-item-outside-wrapper .autotime, > .thread-wrapper .autotime",this).timeago();
			}
			prev = ident;
		});
	}
	if(mode === 'append') {

		next = 'threads-end';



		$('.thread-wrapper.toplevel_item',data).each(function() {


			var ident = $(this).attr('id');
			var commentWrap = $('#'+ident+' .collapsed-comments').attr('id');
			var itmId = 0;
			var isVisible = false;

			if(typeof commentWrap !== 'undefined')
				itmId = commentWrap.replace('collapsed-comments-','');

			if($('#' + ident).length == 0) {
				$('img',this).each(function() {
					$(this).attr('src',$(this).attr('dst'));
				});
				if($('#collapsed-comments-'+itmId).is(':visible'))
					isVisible = true;
				$('#threads-end').before($(this));
				if(isVisible)
					showHideComments(itmId);
				$("> .wall-item-outside-wrapper .autotime, > .thread-wrapper .autotime",this).timeago();
			}
			else {
				$('img',this).each(function() {
					$(this).attr('src',$(this).attr('dst'));
				});
				if($('#collapsed-comments-'+itmId).is(':visible'))
					isVisible = true;
				$('#' + ident).replaceWith($(this));
				if(isVisible)
					showHideComments(itmId);
				$("> .wall-item-outside-wrapper .autotime, > .thread-wrapper .autotime",this).timeago();
			}
		});

		if(loadingPage) {
			loadingPage = false;
		}
	}
	if(mode === 'replace') {
		// clear existing content
		$('.thread-wrapper').remove();

		prev = 'threads-begin';

		$('.thread-wrapper.toplevel_item',data).each(function() {

			var ident = $(this).attr('id');
			var commentWrap = $('#'+ident+' .collapsed-comments').attr('id');
			var itmId = 0;
			var isVisible = false;

			if(typeof commentWrap !== 'undefined')
				itmId = commentWrap.replace('collapsed-comments-','');

			if($('#' + ident).length == 0 && profile_page == 1) {
				$('img',this).each(function() {
					$(this).attr('src',$(this).attr('dst'));
				});

				if($('#collapsed-comments-'+itmId).is(':visible'))
					isVisible = true;
				$('#' + prev).after($(this));
				if(isVisible)
					showHideComments(itmId);
				$("> .wall-item-outside-wrapper .autotime, > .thread-wrapper .autotime",this).timeago();

			}
			prev = ident;
		});

		if(loadingPage) {
			loadingPage = false;
		}
	}

	$('.like-rotator').spin(false);

	if(commentBusy) {
		commentBusy = false;
		$('body').css('cursor', 'auto');
	}

	/* autocomplete @nicknames */
	$(".comment-edit-form  textarea").editor_autocomplete(baseurl+"/acl?f=&n=1");
	
	var bimgs = $(".wall-item-body img").not(function() { return this.complete; });
	var bimgcount = bimgs.length;

	if (bimgcount) {
		bimgs.load(function() {
				bimgcount--;
				if (! bimgcount) {
					collapseHeight();

				}
			});
	} else {
		collapseHeight();
	}

}


	function collapseHeight() {
		$(".wall-item-body, .contact-info").each(function() {
			if($(this).height() > divmore_height + 10) {
				if(! $(this).hasClass('divmore')) {
					$(this).readmore({
						collapsedHeight: divmore_height, 
						moreLink: '<a href="#" class="divgrow-showmore">'+aStr['divgrowmore']+'</a>',
						lessLink: '<a href="#" class="divgrow-showmore">'+aStr['divgrowless']+'</a>'
					});
					$(this).addClass('divmore');
				}
			}
		});
	}

	function liveUpdate() {
		if(typeof profile_uid === 'undefined') profile_uid = false; /* Should probably be unified with channelId defined in head.tpl */
		if((src == null) || (stopped) || (! profile_uid)) { $('.like-rotator').spin(false); return; }
		if(($('.comment-edit-text-full').length) || (in_progress)) {
			if(livetime) {
				clearTimeout(livetime);
			}
			livetime = setTimeout(liveUpdate, 10000);
			return;
		}
		if(livetime != null)
			livetime = null;

		prev = 'live-' + src;

		in_progress = true;

		var update_url;
		var update_mode;

		if(scroll_next) {
			bParam_page = next_page;
			page_load = true;
		}
		else {
			bParam_page = 1;
		}

		update_url = buildCmd();

		if(page_load) {
			$("#page-spinner").spin('small');
			if(bParam_page == 1)
				update_mode = 'replace';
			else
				update_mode = 'append';
		}
		else
			update_mode = 'update';

		$.get(update_url,function(data) {
			page_load = false;
			scroll_next = false;
			updateConvItems(update_mode,data);
			$("#page-spinner").spin(false);
			$("#profile-jot-text-loading").spin(false);

			in_progress = false;

			// FIXME - the following lines were added so that almost
			// immediately after we update the posts on the page, we
			// re-check and update the notification counts.
			// As it turns out this causes a bit of an inefficiency
			// as we're pinging twice for every update, once before
			// and once after. A btter way to do this is to rewrite
			// NavUpdate and perhaps LiveUpdate so that we check for 
			// post updates first and only call the notification ping 
			// once. 

			updateCountsOnly = true;
			if(timer) clearTimeout(timer);
			timer = setTimeout(NavUpdate,10);
		});


	}

	function pageUpdate() {

		in_progress = true;

		var update_url;
		var update_mode;

		if(scroll_next) {
			bParam_page = next_page;
			page_load = true;
		}
		else {
			bParam_page = 1;
		}

		update_url = baseurl + '/' + page_query + '/?f=&aj=1&page=' + bParam_page + extra_args ;

		$("#page-spinner").spin('small');
		update_mode = 'append';

		$.get(update_url,function(data) {
			page_load = false;
			scroll_next = false;
			updatePageItems(update_mode,data);
			$("#page-spinner").spin(false);
			in_progress = false;
		});

	}

	function justifyPhotos() {
		justifiedGalleryActive = true;
		$('#photo-album-contents').justifiedGallery({
			margins: 3,
			border: 0,
			ignoreElement: '#page-end',
			sizeRangeSuffixes: {
				'lt100': '-2',
				'lt240': '-2',
				'lt320': '-2',
				'lt500': '',
				'lt640': '-1',
				'lt1024': '-0'
			}
		}).on('jg.complete', function(e){ justifiedGalleryActive = false; });
	}

	function justifyPhotosAjax() {
		justifiedGalleryActive = true;
		$('#photo-album-contents').justifiedGallery('norewind').on('jg.complete', function(e){ justifiedGalleryActive = false; });
	}

	function notify_popup_loader(notifyType) {

		/* notifications template */
		var notifications_tpl= unescape($("#nav-notifications-template[rel=template]").html());
		var notifications_all = unescape($('<div>').append( $("#nav-" + notifyType + "-see-all").clone() ).html()); //outerHtml hack
		var notifications_mark = unescape($('<div>').append( $("#nav-" + notifyType + "-mark-all").clone() ).html()); //outerHtml hack
		var notifications_empty = unescape($("#nav-" + notifyType + "-menu").html());
		
		var notify_menu = $("#nav-" + notifyType + "-menu");

		var pingExCmd = 'ping/' + notifyType + ((localUser != 0) ? '?f=&uid=' + localUser : '');
		$.get(pingExCmd,function(data) {

			if(data.invalid == 1) { 
				window.location.href=window.location.href 
			}


			if(data.notify.length==0){
				$("#nav-" + notifyType + "-menu").html(aStr[nothingnew]);

			} else {
				$("#nav-" + notifyType + "-menu").html(notifications_all + notifications_mark);

				$(data.notify).each(function() {
					html = notifications_tpl.format(this.notify_link,this.photo,this.name,this.message,this.when,this.hclass);
					$("#nav-" + notifyType + "-menu").append(html);
				});
				$(".dropdown-menu img[data-src]").each(function(i, el){
					// Replace data-src attribute with src attribute for every image
					$(el).attr('src', $(el).data("src"));
					$(el).removeAttr("data-src");
				});
			}
		});

	}


	// Since our ajax calls are asynchronous, we will give a few 
	// seconds for the first ajax call (setting like/dislike), then 
	// run the updater to pick up any changes and display on the page.
	// The updater will turn any rotators off when it's done. 
	// This function will have returned long before any of these
	// events have completed and therefore there won't be any
	// visible feedback that anything changed without all this
	// trickery. This still could cause confusion if the "like" ajax call
	// is delayed and NavUpdate runs before it completes.


	function dolike(ident,verb) {
		unpause();
		$('#like-rotator-' + ident.toString()).spin('tiny');
		$.get('like/' + ident.toString() + '?verb=' + verb, NavUpdate );
		liking = 1;
	}

	function doprofilelike(ident,verb) {
		$.get('like/' + ident + '?verb=' + verb, function() { window.location.href=window.location.href; });
	}



	function dosubthread(ident) {
		unpause();
		$('#like-rotator-' + ident.toString()).spin('tiny');
		$.get('subthread/' + ident.toString(), NavUpdate );
		liking = 1;
	}


	function dostar(ident) {
		ident = ident.toString();
		$('#like-rotator-' + ident).spin('tiny');
		$.get('starred/' + ident, function(data) {
			if(data.result == 1) {
				$('#starred-' + ident).addClass('starred');
				$('#starred-' + ident).removeClass('unstarred');
				$('#starred-' + ident).addClass('icon-star-full');
				$('#starred-' + ident).removeClass('icon-star-empty');
				$('#star-' + ident).addClass('hidden');
				$('#unstar-' + ident).removeClass('hidden');
			}
			else {			
				$('#starred-' + ident).addClass('unstarred');
				$('#starred-' + ident).removeClass('starred');
				$('#starred-' + ident).addClass('icon-star-empty');
				$('#starred-' + ident).removeClass('icon-star-full');
				$('#star-' + ident).removeClass('hidden');
				$('#unstar-' + ident).addClass('hidden');
			}
			$('#like-rotator-' + ident).spin(false);	
		});
	}

	function getPosition(e) {
		var cursor = {x:0, y:0};
		if ( e.pageX || e.pageY  ) {
			cursor.x = e.pageX;
			cursor.y = e.pageY;
		}
		else {
			if( e.clientX || e.clientY ) {
				cursor.x = e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft) - document.documentElement.clientLeft;
				cursor.y = e.clientY + (document.documentElement.scrollTop  || document.body.scrollTop)  - document.documentElement.clientTop;
			}
			else {
				if( e.x || e.y ) {
					cursor.x = e.x;
					cursor.y = e.y;
				}
			}
		}
		return cursor;
	}

	function lockview(event,id) {
		$.get('lockview/' + id, function(data) {
			$('#panel-' + id).html(data);
		});
	}

	function filestorage(event,nick,id) {
		$('#cloud-index-' + last_filestorage_id).removeClass('cloud-index-active');
		$('#perms-panel-' + last_filestorage_id).hide().html('');
		$('#file-edit-' + id).spin('tiny');
		delete acl;
		$.get('filestorage/' + nick + '/' + id + '/edit', function(data) {
			$('#cloud-index-' + id).addClass('cloud-index-active');
			$('#perms-panel-' + id).html(data).show();
			$('#file-edit-' + id).spin(false);
			last_filestorage_id = id;
		});
	}

	function post_comment(id) {
		unpause();
		commentBusy = true;
		$('body').css('cursor', 'wait');
		$("#comment-preview-inp-" + id).val("0");
		$.post(  
             "item",  
             $("#comment-edit-form-" + id).serialize(),
			function(data) {
				if(data.success) {
					$("#comment-edit-preview-" + id).hide();
					$("#comment-edit-wrapper-" + id).hide();
					$("#comment-edit-text-" + id).val('');
    	  			var tarea = document.getElementById("comment-edit-text-" + id);
					if(tarea)
						commentClose(tarea,id);
					if(timer) clearTimeout(timer);
					timer = setTimeout(NavUpdate,1500);
				}
				if(data.reload) {
					window.location.href=data.reload;
				}
			},
			"json"  
         );  
         return false;  
	}


	function preview_comment(id) {
		$("#comment-preview-inp-" + id).val("1");
		$("#comment-edit-preview-" + id).show();
		$.post(  
             "item",  
             $("#comment-edit-form-" + id).serialize(),
			function(data) {
				if(data.preview) {
						
					$("#comment-edit-preview-" + id).html(data.preview);
					$("#comment-edit-preview-" + id + " a").click(function() { return false; });
				}
			},
			"json"  
         );  
         return true;  
	}

	function importElement(elem) {
		$.post(  
             "impel",  
            { "element" : elem },
			function(data) {
				if(timer) clearTimeout(timer);
				timer = setTimeout(NavUpdate,10);
			}
		);

		return false;  
	}

	function preview_post() {
		$("#jot-preview").val("1");
		$("#jot-preview-content").show();
//		tinyMCE.triggerSave();
		$.post(  
			"item",  
			$("#profile-jot-form").serialize(),
			function(data) {
				if(data.preview) {			
					$("#jot-preview-content").html(data.preview);
					$("#jot-preview-content" + " a").click(function() { return false; });
				}
			},
			"json"  
		);  
		$("#jot-preview").val("0");
		return true;  
	}


	function unpause() {
		// unpause auto reloads if they are currently stopped
		totStopped = false;
		stopped = false;
	    $('#pause').html('');
	}
		

    function bin2hex(s){  
        // Converts the binary representation of data to hex    
        //   
        // version: 812.316  
        // discuss at: http://phpjs.org/functions/bin2hex  
        // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)  
        // +   bugfixed by: Onno Marsman  
        // +   bugfixed by: Linuxworld  
        // *     example 1: bin2hex('Kev');  
        // *     returns 1: '4b6576'  
        // *     example 2: bin2hex(String.fromCharCode(0x00));  
        // *     returns 2: '00'  
        var v,i, f = 0, a = [];  
        s += '';  
        f = s.length;  
          
        for (i = 0; i<f; i++) {  
            a[i] = s.charCodeAt(i).toString(16).replace(/^([\da-f])$/,"0$1");  
        }  
          
        return a.join('');  
    }  

	function hex2bin(hex) {
    	var bytes = [], str;

    	for(var i=0; i< hex.length-1; i+=2)
        	bytes.push(parseInt(hex.substr(i, 2), 16));

    	return String.fromCharCode.apply(String, bytes);    
	}

	function groupChangeMember(gid, cid, sec_token) {
		$('body .fakelink').css('cursor', 'wait');
		$.get('group/' + gid + '/' + cid + "?t=" + sec_token, function(data) {
				$('#group-update-wrapper').html(data);
				$('body .fakelink').css('cursor', 'auto');				
		});
	}

	function profChangeMember(gid,cid) {
		$('body .fakelink').css('cursor', 'wait');
		$.get('profperm/' + gid + '/' + cid, function(data) {
				$('#prof-update-wrapper').html(data);
				$('body .fakelink').css('cursor', 'auto');				
		});
	}

	function contactgroupChangeMember(gid,cid) {
		$('body').css('cursor', 'wait');
		$.get('contactgroup/' + gid + '/' + cid, function(data) {
				$('body').css('cursor', 'auto');
				$('#group-' + gid).toggleClass('icon-check icon-check-empty');
		});
	}


function checkboxhighlight(box) {
  if($(box).is(':checked')) {
	$(box).addClass('checkeditem');
  }
  else {
	$(box).removeClass('checkeditem');
  }
}


// code from http://www.tinymce.com/wiki.php/How-to_implement_a_custom_file_browser
function fcFileBrowser (field_name, url, type, win) {
    /* TODO: If you work with sessions in PHP and your client doesn't accept cookies you might need to carry
       the session name and session ID in the request string (can look like this: "?PHPSESSID=88p0n70s9dsknra96qhuk6etm5").
       These lines of code extract the necessary parameters and add them back to the filebrowser URL again. */


    var cmsURL = baseurl+"/fbrowser/"+type+"/";

    tinyMCE.activeEditor.windowManager.open({
        file : cmsURL,
        title : 'File Browser',
        width : 420,  // Your dimensions may differ - toy around with them!
        height : 400,
        resizable : "yes",
        inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
        close_previous : "no"
    }, {
        window : win,
        input : field_name
    });
    return false;
  }

function setupFieldRichtext(){
	return;
/*
	tinyMCE.init({
		theme : "advanced",
		mode : "specific_textareas",
		editor_selector: "fieldRichtext",
		plugins : "bbcode,paste, inlinepopups",
		theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,formatselect,code",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "center",
		theme_advanced_blockformats : "blockquote,code",
		paste_text_sticky : true,
		entity_encoding : "raw",
		add_unload_trigger : false,
		remove_linebreaks : false,
		force_p_newlines : false,
		force_br_newlines : true,
		forced_root_block : '',
		convert_urls: false,
		content_css: baseurl+"/view/custom_tinymce.css",
		theme_advanced_path : false,
		file_browser_callback : "fcFileBrowser",
	});
*/
}


/** 
 * sprintf in javascript 
 *	"{0} and {1}".format('zero','uno'); 
 **/
String.prototype.format = function() {
    var formatted = this;
    for (var i = 0; i < arguments.length; i++) {
        var regexp = new RegExp('\\{'+i+'\\}', 'gi');
        formatted = formatted.replace(regexp, arguments[i]);
    }
    return formatted;
};
// Array Remove
Array.prototype.remove = function(item) {
  to=undefined; from=this.indexOf(item);
  var rest = this.slice((to || from) + 1 || this.length);
  this.length = from < 0 ? this.length + from : from;
  return this.push.apply(this, rest);
};

function previewTheme(elm) {
	theme = $(elm).val();
	$.getJSON('pretheme?f=&theme=' + theme,function(data) {
			$('#theme-preview').html('<div id="theme-desc">' + data.desc + '</div><div id="theme-version">' + data.version + '</div><div id="theme-credits">' + data.credits + '</div><a href="' + data.img + '"><img src="' + data.img + '" width="320" height="240" alt="' + theme + '" /></a>');
	});

}

$(document).ready(function() {

	jQuery.timeago.settings.strings = {
		prefixAgo     : aStr['t01'],
		prefixFromNow : aStr['t02'],
		suffixAgo     : aStr['t03'],
		suffixFromNow : aStr['t04'],
		seconds       : aStr['t05'],
		minute        : aStr['t06'],
		minutes       : aStr['t07'],
		hour          : aStr['t08'],
		hours         : aStr['t09'],
		day           : aStr['t10'],
		days          : aStr['t11'],
		month         : aStr['t12'],
		months        : aStr['t13'],
		year          : aStr['t14'],
		years         : aStr['t15'],
		wordSeparator : aStr['t16'],
		numbers       : aStr['t17'],
	};


	$(".autotime").timeago();
	$("#toc").toc();
	

});

	function zFormError(elm,x) {
		if(x) {
			$(elm).addClass("zform-error");
			$(elm).removeClass("zform-ok");
		}
		else {
			$(elm).addClass("zform-ok");
			$(elm).removeClass("zform-error");
		}											
	}



$(window).scroll(function () {
	if(typeof buildCmd == 'function') {
		// This is a content page with items and/or conversations
		$('#more').hide();                 
		$('#no-more').hide();                 
	
		if($(window).scrollTop() + $(window).height() > $(document).height() - 200) {
			$('#more').css("top","400");
			$('#more').show();
		}

		if($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
//		if($(window).scrollTop() > ($(document).height() - $(window).height() * 1.5 )) {

			if((pageHasMoreContent) && (! loadingPage)) {
				$('#more').hide();
				$('#no-more').hide();

				next_page++;
				scroll_next = true;
				loadingPage = true;
				liveUpdate();
			}
		}
	}
	else {
		// This is some other kind of page - perhaps a directory

		if($(window).scrollTop() + $(window).height() > $(document).height() - 200) {
			$('#more').css("top","400");
			$('#more').show();
		}

		if($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
//		if($(window).scrollTop() > ($(document).height() - $(window).height() * 1.5 )) {
			if((pageHasMoreContent) && (! loadingPage) && (! justifiedGalleryActive)) {
				$('#more').hide();
				$('#no-more').hide();

				next_page++;
				scroll_next = true;
				loadingPage = true;
				pageUpdate();
			}
		}
	}

});

var chanviewFullSize = false;

function chanviewFull() {
	if(chanviewFullSize) {
		chanviewFullSize = false;
		$('#chanview-iframe-border').css({ 'position' : 'relative', 'z-index' : '10' });
		$('#remote-channel').css({ 'position' : 'relative' , 'z-index' : '10' });
	}
	else {
		chanviewFullSize = true;
		$('#chanview-iframe-border').css({ 'position' : 'fixed', 'top' : '0', 'left' : '0', 'z-index' : '150001' });
		$('#remote-channel').css({ 'position' : 'fixed', 'top' : '0', 'left' : '0', 'z-index' : '150000' });
		resize_iframe();
	}
}

	function addhtmltext(data) {
		data = h2b(data);
		addeditortext(data);
	}

	function addeditortext(data) {
		if(plaintext == 'none') {
			var currentText = $("#profile-jot-text").val();
			$("#profile-jot-text").val(currentText + data);
		}
		else
			tinyMCE.execCommand('mceInsertRawHTML',false,data);
	}


    function h2b(s) {
		var y = s;
		function rep(re, str) {
			y = y.replace(re,str);
		};

		rep(/<a.*?href=\"(.*?)\".*?>(.*?)<\/a>/gi,"[url=$1]$2[/url]");
		rep(/<span style=\"font-size:(.*?);\">(.*?)<\/span>/gi,"[size=$1]$2[/size]");
		rep(/<span style=\"color:(.*?);\">(.*?)<\/span>/gi,"[color=$1]$2[/color]");
		rep(/<font>(.*?)<\/font>/gi,"$1");
		rep(/<img.*?width=\"(.*?)\".*?height=\"(.*?)\".*?src=\"(.*?)\".*?\/>/gi,"[img=$1x$2]$3[/img]");
		rep(/<img.*?height=\"(.*?)\".*?width=\"(.*?)\".*?src=\"(.*?)\".*?\/>/gi,"[img=$2x$1]$3[/img]");
		rep(/<img.*?src=\"(.*?)\".*?height=\"(.*?)\".*?width=\"(.*?)\".*?\/>/gi,"[img=$3x$2]$1[/img]");
		rep(/<img.*?src=\"(.*?)\".*?width=\"(.*?)\".*?height=\"(.*?)\".*?\/>/gi,"[img=$2x$3]$1[/img]");
		rep(/<img.*?src=\"(.*?)\".*?\/>/gi,"[img]$1[/img]");


		rep(/<ul class=\"listbullet\" style=\"list-style-type\: circle\;\">(.*?)<\/ul>/gi,"[list]$1[/list]");
		rep(/<ul class=\"listnone\" style=\"list-style-type\: none\;\">(.*?)<\/ul>/gi,"[list=]$1[/list]");
		rep(/<ul class=\"listdecimal\" style=\"list-style-type\: decimal\;\">(.*?)<\/ul>/gi,"[list=1]$1[/list]");
		rep(/<ul class=\"listlowerroman\" style=\"list-style-type\: lower-roman\;\">(.*?)<\/ul>/gi,"[list=i]$1[/list]");
		rep(/<ul class=\"listupperroman\" style=\"list-style-type\: upper-roman\;\">(.*?)<\/ul>/gi,"[list=I]$1[/list]");
		rep(/<ul class=\"listloweralpha\" style=\"list-style-type\: lower-alpha\;\">(.*?)<\/ul>/gi,"[list=a]$1[/list]");
		rep(/<ul class=\"listupperalpha\" style=\"list-style-type\: upper-alpha\;\">(.*?)<\/ul>/gi,"[list=A]$1[/list]");
		rep(/<li>(.*?)<\/li>/gi,"[li]$1[/li]");

		rep(/<code>(.*?)<\/code>/gi,"[code]$1[/code]");
		rep(/<\/(strong|b)>/gi,"[/b]");
		rep(/<(strong|b)>/gi,"[b]");
		rep(/<\/(em|i)>/gi,"[/i]");
		rep(/<(em|i)>/gi,"[i]");
		rep(/<\/u>/gi,"[/u]");


		rep(/<span style=\"text-decoration: ?underline;\">(.*?)<\/span>/gi,"[u]$1[/u]");
		rep(/<u>/gi,"[u]");
		rep(/<blockquote[^>]*>/gi,"[quote]");
		rep(/<\/blockquote>/gi,"[/quote]");
		rep(/<hr \/>/gi,"[hr]");
		rep(/<br (.*?)\/>/gi,"\n");
		rep(/<br\/>/gi,"\n");
		rep(/<br>/gi,"\n");
		rep(/<p>/gi,"");
		rep(/<\/p>/gi,"\n");
		rep(/&nbsp;/gi," ");
		rep(/&quot;/gi,"\"");
		rep(/&lt;/gi,"<");
		rep(/&gt;/gi,">");
		rep(/&amp;/gi,"&");

		return y; 
	};


    function b2h(s) {
		var y = s;
		function rep(re, str) {
			y = y.replace(re,str);
		};

		rep(/\&/gi,"&amp;");
		rep(/\</gi,"&lt;");
		rep(/\>/gi,"&gt;");
		rep(/\"/gi,"&quot;");

		rep(/\n/gi,"<br />");
		rep(/\[b\]/gi,"<strong>");
		rep(/\[\/b\]/gi,"</strong>");
		rep(/\[i\]/gi,"<em>");
		rep(/\[\/i\]/gi,"</em>");
		rep(/\[u\]/gi,"<u>");
		rep(/\[\/u\]/gi,"</u>");
		rep(/\[hr\]/gi,"<hr />");
		rep(/\[url=([^\]]+)\](.*?)\[\/url\]/gi,"<a href=\"$1\">$2</a>");
		rep(/\[url\](.*?)\[\/url\]/gi,"<a href=\"$1\">$1</a>");
		rep(/\[img=(.*?)x(.*?)\](.*?)\[\/img\]/gi,"<img width=\"$1\" height=\"$2\" src=\"$3\" />");
		rep(/\[img\](.*?)\[\/img\]/gi,"<img src=\"$1\" />");

		// FIXME - add zid()
		rep(/\[zrl=([^\]]+)\](.*?)\[\/zrl\]/gi,"<a href=\"$1" + '?f=&zid=' + zid + "\">$2</a>");
		rep(/\[zrl\](.*?)\[\/zrl\]/gi,"<a href=\"$1" + '?f=&zid=' + zid + "\">$1</a>");
		rep(/\[zmg=(.*?)x(.*?)\](.*?)\[\/zmg\]/gi,"<img width=\"$1\" height=\"$2\" src=\"$3" + '?f=&zid=' + zid + "\" />");
		rep(/\[zmg\](.*?)\[\/zmg\]/gi,"<img src=\"$1" + '?f=&zid=' + zid + "\" />");

		rep(/\[list\](.*?)\[\/list\]/gi, '<ul class="listbullet" style="list-style-type: circle;">$1</ul>');
		rep(/\[list=\](.*?)\[\/list\]/gi, '<ul class="listnone" style="list-style-type: none;">$1</ul>');
		rep(/\[list=1\](.*?)\[\/list\]/gi, '<ul class="listdecimal" style="list-style-type: decimal;">$1</ul>');
		rep(/\[list=i\](.*?)\[\/list\]/gi,'<ul class="listlowerroman" style="list-style-type: lower-roman;">$1</ul>');
		rep(/\[list=I\](.*?)\[\/list\]/gi, '<ul class="listupperroman" style="list-style-type: upper-roman;">$1</ul>');
		rep(/\[list=a\](.*?)\[\/list\]/gi, '<ul class="listloweralpha" style="list-style-type: lower-alpha;">$1</ul>');
		rep(/\[list=A\](.*?)\[\/list\]/gi, '<ul class="listupperalpha" style="list-style-type: upper-alpha;">$1</ul>');
		rep(/\[li\](.*?)\[\/li\]/gi, '<li>$1</li>');
		rep(/\[color=(.*?)\](.*?)\[\/color\]/gi,"<span style=\"color: $1;\">$2</span>");
		rep(/\[size=(.*?)\](.*?)\[\/size\]/gi,"<span style=\"font-size: $1;\">$2</span>");
		rep(/\[code\](.*?)\[\/code\]/gi,"<code>$1</code>");
		rep(/\[quote.*?\](.*?)\[\/quote\]/gi,"<blockquote>$1</blockquote>");

		rep(/\[video\](.*?)\[\/video\]/gi,"<a href=\"$1\">$1</a>");
		rep(/\[audio\](.*?)\[\/audio\]/gi,"<a href=\"$1\">$1</a>");

		rep(/\[\&amp\;([#a-z0-9]+)\;\]/gi,'&$1;');

		rep(/\<(.*?)(src|href)=\"[^hfm](.*?)\>/gi,'<$1$2="">');

		return y; 
	};


function zid(s) {
	if((! s.length) || (s.indexOf('zid=') != (-1)))
		return s;
	if(! zid.length)
		return s;
	var has_params = ((s.indexOf('?') == (-1)) ? false : true);
	var achar = ((has_params) ? '&' : '?');
	s = s + achar + 'f=&zid=' + zid;
	return s;
}
