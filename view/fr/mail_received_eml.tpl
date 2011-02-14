--$mimeboundary
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: 8bit

$from t'a envoyé un message à $siteName.
	
$title

$textversion

Ouvrez une session svp à $siteurl pour lire et répondre à vos messages privés.

Merci,
$siteName Administrateur

--$mimeboundary
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: 8bit
				
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional //EN">
<html>
<head>
	<title>Friendika Message</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
</head>
<body>
<table style="border:1px solid #ccc">
	<tbody>
	<tr><td colspan="2" style="background:#3b5998; color:#FFFFFF; font-weight:bold; font-family:'lucida grande', tahoma, verdana,arial, sans-serif; padding: 4px 8px; vertical-align: middle; font-size:16px; letter-spacing: -0.03em; text-align: left;"><img style="width:32px;height:32px;" src='$hostname/images/ff-32.jpg'><span style="padding:7px;">Friendika</span></td></tr>

	<tr><td style="padding-top:22px;" colspan="2">$from t'a envoyé un message à $siteName.</td></tr>


	<tr><td style="padding-left:22px;padding-top:22px;width:60px;" valign="top" rowspan=3><a href="$url"><img style="border:0px;width:48px;height:48px;" src="$thumb"></a></td>
		<td style="padding-top:22px;"><a href="$url">$from</a></td></tr>
	<tr><td style="font-weight:bold;padding-bottom:5px;">$title</td></tr>
	<tr><td style="padding-right:22px;">$htmlversion</td></tr>
	<tr><td style="padding-top:11px;padding-bottom:11px;" colspan="2">Ouvrez une session svp à $siteurl pour lire et répondre à vos messages privés.</td></tr>
	<tr><td></td><td>Merci,</td></tr>
	<tr><td></td><td>$siteName Administrateur</td></tr>
	</tbody>
</table>
</body>
</html>

--$mimeboundary--