
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

	$(function() {
		$.ajaxSetup({cache: false});

		msie = $.browser.msie ;
		
		/* setup tooltips *//*
		$("a,.tt").each(function(){
			var e = $(this);
			var pos="bottom";
			if (e.hasClass("tttop")) pos="top";
			if (e.hasClass("ttbottom")) pos="bottom";
			if (e.hasClass("ttleft")) pos="left";
			if (e.hasClass("ttright")) pos="right";
			e.tipTip({defaultPosition: pos, edgeOffset: 8});
		});*/
		
		
		
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

		/* popup menus */
	function close_last_popup_menu() {
 		if(last_popup_menu) {
 		last_popup_menu.hide();
 		last_popup_button.removeClass("selected");
 		last_popup_menu = null;
 		last_popup_button = null;
 		}
 		}
		$('a[rel^=#]').click(function(e){
			close_last_popup_menu();
			menu = $( $(this).attr('rel') );
			e.preventDefault();
			e.stopPropagation();
			if (menu.attr('popup')=="false") return false;
			$(this).parent().toggleClass("selected");
			menu.toggle();
			if (menu.css("display") == "none") {
				last_popup_menu = null;
				last_popup_button = null;
			} else {
				last_popup_menu = menu;
				last_popup_button = $(this).parent();
			}
			return false;
		});
		$('html').click(function() {
						close_last_popup_menu();
		});
		
		// fancyboxes
		$("a.popupbox").fancybox({
			'transitionIn' : 'elastic',
			'transitionOut' : 'elastic'
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

		if(! stopped) {

			var pingCmd = 'ping' + ((localUser != 0) ? '?f=&uid=' + localUser : '');

			$.get(pingCmd,function(data) {

				if(data.invalid == 1) { 
					window.location.href=window.location.href 
				}

				if(data.network == 0) { 
					data.network = ''; 
					$('#net-update').removeClass('show') 
				} 
				else { 
					$('#net-update').addClass('show') 
				}
				$('#net-update').html(data.network);

				if(data.home == 0) { data.home = ''; $('#home-update').removeClass('show') } else { $('#home-update').addClass('show') }
				$('#home-update').html(data.home);
			

				if(data.intros == 0) { data.intros = ''; $('#intro-update').removeClass('show') } else { $('#intro-update').addClass('show') }
				$('#intro-update').html(data.intros);

				if(data.mail == 0) { data.mail = ''; $('#mail-update').removeClass('show') } else { $('#mail-update').addClass('show') }
				$('#mail-update').html(data.mail);
			

				if(data.notify == 0) { data.notify = ''; $('#notify-update').removeClass('show') } else { $('#notify-update').addClass('show') }
				$('#notify-update').html(data.notify);

				

				$(data.notice).each(function() {
					$.jGrowl(this.message, { sticky: true, theme: 'notice' });
				});

				$(data.info).each(function(){
					$.jGrowl(this.message, { sticky: false, theme: 'info', life: 10000 });
				});

			
				// start live update

				if($('#live-network').length)   { src = 'network'; liveUpdate(); }
				if($('#live-profile').length)   { src = 'profile'; liveUpdate(); }
				if($('#live-community').length) { src = 'community'; liveUpdate(); }
				if($('#live-notes').length)     { src = 'notes'; liveUpdate(); }
				if($('#live-display').length) {
					if(liking) {
						liking = 0;
						window.location.href=window.location.href 
					}
				}
				if($('#live-photos').length) { 
					if(liking) {
						liking = 0;
						window.location.href=window.location.href 
					}
				}

			}) ;
		}
		timer = setTimeout(NavUpdate,updateInterval);
	}

	function liveUpdate() {
		if((src == null) || (stopped) || (! profile_uid)) { $('.like-rotator').hide(); return; }
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
		var udargs = ((netargs.length) ? '/' + netargs : '');
		var update_url = 'update_' + src + udargs + '&p=' + profile_uid + '&page=' + profile_page + '&msie=' + ((msie) ? 1 : 0);

		$.get(update_url,function(data) {
			in_progress = false;
			//			$('.collapsed-comments',data).each(function() {
			//	var ident = $(this).attr('id');
			//	var is_hidden = $('#' + ident).is(':hidden');
			//	if($('#' + ident).length) {
			//		$('#' + ident).replaceWith($(this));
			//		if(is_hidden)
			//			$('#' + ident).hide();
			//	}
			//});

			// add a new thread

			$('.tread-wrapper',data).each(function() {
				var ident = $(this).attr('id');

				if($('#' + ident).length == 0 && profile_page == 1) {
					$('img',this).each(function() {
						$(this).attr('src',$(this).attr('dst'));
					});
					$('#' + prev).after($(this));
				}
				else {
					$('img',this).each(function() {
						$(this).attr('src',$(this).attr('dst'));
					});
					$('#' + ident).replaceWith($(this));
				}
				prev = ident;
			});

			// reset vars for inserting individual items

			/*			prev = 'live-' + src;

			$('.wall-item-outside-wrapper',data).each(function() {
				var ident = $(this).attr('id');

				if($('#' + ident).length == 0 && prev != 'live-' + src) {
						$('img',this).each(function() {
							$(this).attr('src',$(this).attr('dst'));
						});
						$('#' + prev).after($(this));
				}
				else { 
					$('#' + ident + ' ' + '.wall-item-ago').replaceWith($(this).find('.wall-item-ago')); 
					if($('#' + ident + ' ' + '.comment-edit-text-empty').length)
						$('#' + ident + ' ' + '.wall-item-comment-wrapper').replaceWith($(this).find('.wall-item-comment-wrapper'));
					$('#' + ident + ' ' + '.hide-comments-total').replaceWith($(this).find('.hide-comments-total'));
					$('#' + ident + ' ' + '.wall-item-like').replaceWith($(this).find('.wall-item-like'));
					$('#' + ident + ' ' + '.wall-item-dislike').replaceWith($(this).find('.wall-item-dislike'));
					$('#' + ident + ' ' + '.my-comment-photo').each(function() {
						$(this).attr('src',$(this).attr('dst'));
					});
				}
				prev = ident; 
			});
			*/
			$('.like-rotator').hide();
			if(commentBusy) {
				commentBusy = false;
				$('body').css('cursor', 'auto');
			}
			/* autocomplete @nicknames */
			$(".comment-edit-form  textarea").contact_autocomplete(baseurl+"/acl");
		});
	}

	function imgbright(node) {
		$(node).removeClass("drophide").addClass("drop");
	}

	function imgdull(node) {
		$(node).removeClass("drop").addClass("drophide");
	}

	function notify_popup() {

		if($("#nav-notifications-menu").is(":visible")) {
			$("#nav-notifications-menu").hide();
			return;
		}

		/* notifications template */
		var notifications_tpl= unescape($("#nav-notifications-template[rel=template]").html());
		var notifications_all = unescape($('<div>').append( $("#nav-notifications-see-all").clone() ).html()); //outerHtml hack
		var notifications_mark = unescape($('<div>').append( $("#nav-notifications-mark-all").clone() ).html()); //outerHtml hack
		var notifications_empty = unescape($("#nav-notifications-menu").html());
		
		var notify_menu = $("#nav-notifications-menu");

		var pingExCmd = 'ping/notify' + ((localUser != 0) ? '?f=&uid=' + localUser : '');
		$.get(pingExCmd,function(data) {

			if(data.invalid == 1) { 
				window.location.href=window.location.href 
			}


			if(data.notify.length==0){
				$("#nav-notifications-menu").html(notifications_empty);

			} else {
				$("#nav-notifications-menu").html(notifications_all + notifications_mark);


				$(data.notify).each(function() {
					text = "<span class='contactname'>"+this.name+"</span>" + ' ' + this.message;
					html = notifications_tpl.format(this.notify_link,this.photo,text,this.when,this.class);
					$("#nav-notifications-menu").append(html);
				});

			}
		});

		$("#nav-notifications-menu").show();


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
		$('#like-rotator-' + ident.toString()).show();
		$.get('like/' + ident.toString() + '?verb=' + verb, NavUpdate );
//		if(timer) clearTimeout(timer);
//		timer = setTimeout(NavUpdate,3000);
		liking = 1;
	}

	function dostar(ident) {
		ident = ident.toString();
		$('#like-rotator-' + ident).show();
		$.get('starred/' + ident, function(data) {
			if(data.match(/1/)) {
				$('#starred-' + ident).addClass('starred');
				$('#starred-' + ident).removeClass('unstarred');
				$('#star-' + ident).addClass('hidden');
				$('#unstar-' + ident).removeClass('hidden');
			}
			else {			
				$('#starred-' + ident).addClass('unstarred');
				$('#starred-' + ident).removeClass('starred');
				$('#star-' + ident).removeClass('hidden');
				$('#unstar-' + ident).addClass('hidden');
			}
			$('#like-rotator-' + ident).hide();	
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

	var lockvisible = false;

	function lockview(event,id) {
		event = event || window.event;
		cursor = getPosition(event);
		if(lockvisible) {
			lockviewhide();
		}
		else {
			lockvisible = true;
			$.get('lockview/' + id, function(data) {
				$('#panel').html(data);
				$('#panel').css({ 'left': cursor.x + 5 , 'top': cursor.y + 5});
				$('#panel').show();
			});
		}
	}

	function lockviewhide() {
		lockvisible = false;
		$('#panel').hide();
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
					$("#comment-edit-wrapper-" + id).hide();
					$("#comment-edit-text-" + id).val('');
    	  			var tarea = document.getElementById("comment-edit-text-" + id);
					if(tarea)
						commentClose(tarea,id);
					if(timer) clearTimeout(timer);
					timer = setTimeout(NavUpdate,10);
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



	function preview_post() {
		$("#jot-preview").val("1");
		$("#jot-preview-content").show();
		tinyMCE.triggerSave();
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

function notifyMarkAll() {
	$.get('notify/mark/all', function(data) {
		if(timer) clearTimeout(timer);
		timer = setTimeout(NavUpdate,1000);
	});
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
			$('#theme-preview').html('<div id="theme-desc">' + data.desc + '</div><a href="' + data.img + '"><img src="' + data.img + '" width="320" height="240" alt="' + theme + '" /></a>');
	});

}

// This can be used for dynamic "show more" based purely on the 
// height of the enclosed HTML (which has overflow: hidden).
// if ($(el).vScrollable()) {
//    show_more_link(el);
// }
// when link is clicked do $(el).css('overflow','visible') 
//
// We do this by cloning the element in question but in a hidden div which displays overflow content and compare the heights
// of the two elements. Then we remove the hidden div we just created.

(function($) {
    $.fn.vScrollable = function() {
        return this.each(function() {
		var el = $(this);
		var tooHigh = false;

		if(el.css("overflow") == "hidden") {
            var text = el.html();                               
            var t = $(this.cloneNode(true))
				.hide()
				.css('position', 'absolute')
				.css('overflow', 'visible')
				.width(el.width())
				.height('auto')
					;
                el.after(t);
				tooHigh = (t.height() > el.height()); 
				el.parentNode.removeChild(el);
		}
		return tooHigh;
	});
    };
})(jQuery);
