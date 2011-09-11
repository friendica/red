
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
	var timer = null;
	var pr = 0;
	var liking = 0;
	var in_progress = false;
	var langSelect = false;
	var commentBusy = false;

	$(function() {
		$.ajaxSetup({cache: false});

		msie = $.browser.msie ;
		
		
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
		
		/* load tinyMCE if needed and setup field_richtext */
		/*if(typeof tinyMCE == "undefined") {
			window.tinyMCEPreInit = {
				suffix:"",
				base: baseurl+"/library/tinymce/jscripts/tiny_mce/",
				query:"",
			};
			$.getScript(baseurl	+"/library/tinymce/jscripts/tiny_mce/tiny_mce_src.js", setupFieldRichtext);
		} else {
		}*/
		
		
		
		/* nav update event  */
		$('nav').bind('nav-update', function(e,data){;
			var net = $(data).find('net').text();
			if(net == 0) { net = ''; $('#net-update').hide() } else { $('#net-update').show() }
			$('#net-update').html(net);
			var home = $(data).find('home').text();
			if(home == 0) { home = '';  $('#home-update').hide() } else { $('#home-update').show() }
			$('#home-update').html(home);
			var mail = $(data).find('mail').text();
			if(mail == 0) { mail = '';  $('#mail-update').hide() } else { $('#mail-update').show() }
			$('#mail-update').html(mail);
			var intro = $(data).find('intro').text();
			if(intro == 0) { intro = ''; $('#notify-update').hide() } else { $('#notify-update').show() }
			$('#notify-update').html(intro);
		});
		
		
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
			else {
				// any key to resume
				if(stopped == true) {
					stopped = false;
					$('#pause').html('');
				}
			}

		});					
	});

	function NavUpdate() {

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
		if($('#live-photos').length)  { 
			if(liking) {
				liking = 0;
				window.location.href=window.location.href 
			}
		}

		if(! stopped) {
			$.get("ping",function(data) {
				$(data).find('result').each(function() {
					// send nav-update event
					$('nav').trigger('nav-update', this);
				});
			}) ;
		}
		timer = setTimeout(NavUpdate,30000);
	}

	function liveUpdate() {
		if((src == null) || (stopped) || (! profile_uid)) { $('.like-rotator').hide(); return; }
		if(($('.comment-edit-text-full').length) || (in_progress)) {
			livetime = setTimeout(liveUpdate, 10000);
			return;
		}
		prev = 'live-' + src;

		in_progress = true;
		var udargs = ((netargs.length) ? '/' + netargs : '');
		var update_url = 'update_' + src + udargs + '&p=' + profile_uid + '&page=' + profile_page + '&msie=' + ((msie) ? 1 : 0);

		$.get(update_url,function(data) {
			in_progress = false;
			$('.ccollapse-wrapper',data).each(function() {
				var ident = $(this).attr('id');
				var is_hidden = $('#' + ident).is(':hidden');
				if($('#' + ident).length) {
					$('#' + ident).replaceWith($(this));
					if(is_hidden)
						$('#' + ident).hide();
				}
			});
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
					if($('#' + ident + ' ' + '.comment-edit-text-empty').length)
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
			if(commentBusy) {
				commentBusy = false;
				$('body').css('cursor', 'auto');
			}
		});
	}

	function imgbright(node) {
		$(node).removeClass("drophide").addClass("drop");
	}

	function imgdull(node) {
		$(node).removeClass("drop").addClass("drophide");
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
		$('#like-rotator-' + ident.toString()).show();
		$.get('like/' + ident.toString() + '?verb=' + verb );
		if(timer) clearTimeout(timer);
		timer = setTimeout(NavUpdate,3000);
		liking = 1;
	}

	function dostar(ident) {
		$('#like-rotator-' + ident.toString()).show();
		$.get('starred/' + ident.toString(), function(data) {
			if(data.match(/1/)) {
				$('#starred-' + ident.toString()).addClass('starred');
				$('#starred-' + ident.toString()).removeClass('unstarred');
			}
			else {			
				$('#starred-' + ident.toString()).addClass('unstarred');
				$('#starred-' + ident.toString()).removeClass('starred');
			}
			$('#like-rotator-' + ident.toString()).hide();	
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
		commentBusy = true;
		$('body').css('cursor', 'wait');
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

	function groupChangeMember(gid,cid) {
		$('body .fakelink').css('cursor', 'wait');
		$.get('group/' + gid + '/' + cid, function(data) {
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

function setupFieldRichtext(){
	tinyMCE.init({
		theme : "advanced",
		mode : "specific_textareas",
		editor_selector: "fieldRichtext",
		plugins : "bbcode,paste",
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

