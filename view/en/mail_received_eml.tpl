--$mimeboundary
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: 8bit

$from sent you a new private message at $siteName.
	
$title

$textversion
				
Please login at $siteurl to read and reply to your private messages.

Thank you,
$siteName administrator

--$mimeboundary
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: 8bit
				
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional //EN">
<html>
<head>
	<title>Friendika Message</title>
	<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1" />
</head>
<body>
<table style="border:1px solid #ccc">
	<tbody>
	<tr><td colspan="2" style="background:#3b5998; color:#FFFFFF; font-weight:bold; font-family:'lucida grande', tahoma, verdana,arial, sans-serif; padding: 4px 8px; vertical-align: middle; font-size:16px; letter-spacing: -0.03em; text-align: left;"><img style="width:32px;height:32px;" src='$hostname/images/ff-32.jpg'><span style="padding:7px;">Friendika</span></td></tr>

	<tr><td style="padding-top:22px;" colspan="2">$from sent you a new private message at $siteName.</td></tr>


	<tr><td style="padding-left:22px;padding-top:22px;width:60px;" valign="top" rowspan=3><a href="$url"><img style="border:0px;width:48px;height:48px;" src="$thumb"></a></td>
		<td style="padding-top:22px;"><a href="$url">$from</a></td></tr>
	<tr><td style="font-weight:bold;padding-bottom:5px;">$title</td></tr>
	<tr><td style="padding-right:22px;">$htmlversion</td></tr>
	<tr><td style="padding-top:11px;padding-bottom:11px;" colspan="2">Please login at $siteurl to read and reply to your private messages.</td></tr>
	<tr><td></td><td>Thank You,</td></tr>
	<tr><td></td><td>$siteName Administrator</td></tr>
	</tbody>
</table>
</body>
</html>

--$mimeboundary--

