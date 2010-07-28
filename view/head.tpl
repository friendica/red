<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<base href="$baseurl" />
<link rel="stylesheet" type="text/css" href="$baseurl/view/style.css" media="all" />

<!--[if IE]>
<script type="text/javascript" src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript" src="$baseurl/include/jquery.js" ></script>
<script type="text/javascript" src="$baseurl/include/main.js" ></script>

<script type="text/javascript">

	var src = null;
	var prev = null;
	var livetime = null;
	var msie = false;

	$(document).ready(function() {
		$.ajaxSetup({cache: false});
		msie = $.browser.msie ;
 		NavUpdate(); 



	});

	function NavUpdate() {

		if($('#live-network').length) { src = 'network'; liveUpdate(); }
		if($('#live-profile').length) { src = 'profile'; liveUpdate(); }

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
		setTimeout(NavUpdate,30000);

	}

	function liveUpdate() {
		if(src == null) { return; }
		if($('.comment-edit-text-full').length) {
			livetime = setTimeout(liveUpdate, 10000);
			return;
		}
		prev = 'live-' + src;

		$.get('update_' + src + '?msie=' + ((msie) ? 1 : 0),function(data) {
			$('.wall-item-outside-wrapper',data).each(function() {
				var ident = $(this).attr('id');
				if($('#' + ident).length == 0) { 
					$('#' + prev).after($(this));
				}
				else { $('#' + ident).replaceWith($(this)); }
				prev = ident; 
			});
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






</script>

