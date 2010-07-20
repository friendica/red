<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<base href="$baseurl" />
<link rel="stylesheet" type="text/css" href="$baseurl/view/style.css" media="all" />

<!--[if IE]>
<script type="text/javascript" src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript" src="$baseurl/include/jquery.js" ></script>
<script type="text/javascript" src="$baseurl/include/main.js" ></script>

<script type="text/javascript">
	$(document).ready(function() { NavUpdate(); });

function NavUpdate()
	{
		$.get("ping",function(data)
			{
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
</script>

