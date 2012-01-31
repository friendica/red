<?php

#   Copyright (C) 2006-2010 Tobias Leupold <tobias.leupold@web.de>
#
#   This file is part of the b8 package
#
#   This program is free software; you can redistribute it and/or modify it
#   under the terms of the GNU Lesser General Public License as published by
#   the Free Software Foundation in version 2.1 of the License.
#
#   This program is distributed in the hope that it will be useful, but
#   WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
#   or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public
#   License for more details.
#
#   You should have received a copy of the GNU Lesser General Public License
#   along with this program; if not, write to the Free Software Foundation,
#   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.


### This is an example script demonstrating how b8 can be used. ###

#/*

# Use this code block if you want to use Berkeley DB.

# The database filename is interpreted relative to the b8.php script location.

$config_b8 = array(
	'storage' => 'dba'
);

$config_database = array(
	'database' => 'wordlist.db',
	'handler'  => 'db4'
);

#*/

/*

# Use this code block if you want to use MySQL.

# An existing link resource can be passed to b8 by setting
# $config_database['connection'] to this link resource.
# Be sure to set your database access data otherwise!

$config_b8 = array(
	'storage' => 'mysql'
);

$config_database = array(
	'database'   => 'test',
	'table_name' => 'b8_wordlist',
	'host'       => 'localhost',
	'user'       => '',
	'pass'       => ''
);

*/

# To be able to calculate the time the classification took

$time_start = NULL;

function microtimeFloat()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float) $usec + (float) $sec);
}

# Output a nicely colored rating

function formatRating($rating)
{

	if($rating === FALSE)
		return "<span style=\"color:red\">could not calculate spaminess</span>";

	$red   = floor(255 * $rating);
	$green = floor(255 * (1 - $rating));

	return "<span style=\"color:rgb($red, $green, 0);\"><b>" . sprintf("%5f", $rating) . "</b></span>";

}

echo <<<END
<?xml version="1.0" encoding="UTF-8"?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
   "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>

<title>example b8 interface</title>

<meta http-equiv="content-type" content="text/html; charset=UTF-8" />

<meta name="dc.creator" content="Tobias Leupold" />
<meta name="dc.rights" content="Copyright (c) by Tobias Leupold" />

</head>

<body>

<div>

<h1>example b8 interface</h1>


END;

$postedText = "";

if(isset($_POST['action']) and $_POST['text'] ==  "")
	echo "<p style=\"color:red;\"><b>Please type in a text!</b></p>\n\n";

elseif(isset($_POST['action']) and $_POST['text'] !=  "") {

	$time_start = microtimeFloat();

	# Include the b8 code
	require dirname(__FILE__) . "/../b8/b8.php";

	# Create a new b8 instance
	$b8 = new b8($config_b8, $config_database);

	# Check if everything worked smoothly

	$started_up = $b8->validate();

	if($started_up !== TRUE) {
		echo "<b>example:</b> Could not initialize b8. error code: $started_up";
		exit;
	}

	$text = stripslashes($_POST['text']);
	$postedText = htmlentities($text, ENT_QUOTES, 'UTF-8');

	switch($_POST['action']) {

		case "Classify":
			echo "<p><b>Spaminess: " . formatRating($b8->classify($text)) . "</b></p>\n";
			break;

		case "Save as Spam":

			$ratingBefore = $b8->classify($text);
			$b8->learn($text, b8::SPAM);
			$ratingAfter = $b8->classify($text);

			echo "<p>Saved the text as Spam</p>\n\n";

			echo "<div><table>\n";
			echo "<tr><td>Classification before learning:</td><td>" . formatRating($ratingBefore) . "</td></tr>\n";
			echo "<tr><td>Classification after learning:</td><td>"  . formatRating($ratingAfter)  . "</td></tr>\n";
			echo "</table></div>\n\n";

			break;

		case "Save as Ham":

			$ratingBefore = $b8->classify($text);
			$b8->learn($text, b8::HAM);
			$ratingAfter = $b8->classify($text);

			echo "<p>Saved the text as Ham</p>\n\n";

			echo "<div><table>\n";
			echo "<tr><td>Classification before learning:</td><td>" . formatRating($ratingBefore) . "</td></tr>\n";
			echo "<tr><td>Classification after learning:</td><td>"  . formatRating($ratingAfter)  . "</td></tr>\n";
			echo "</table></div>\n\n";

			break;

		case "Delete from Spam":
			$b8->unlearn($text, b8::SPAM);
			echo "<p style=\"color:green\">Deleted the text from Spam</p>\n\n";
			break;

		case "Delete from Ham":
			$b8->unlearn($text, b8::HAM);
			echo "<p style=\"color:green\">Deleted the text from Ham</p>\n\n";
			break;

	}

	$mem_used      = round(memory_get_usage() / 1048576, 5);
	$peak_mem_used = round(memory_get_peak_usage() / 1048576, 5);
	$time_taken    = round(microtimeFloat() - $time_start, 5);

}

echo <<<END
<div>
<form action="{$_SERVER['PHP_SELF']}" method="post">
<div>
<textarea name="text" cols="50" rows="16">$postedText</textarea>
</div>
<table>
<tr>
<td><input type="submit" name="action" value="Classify" /></td>
</tr>
<tr>
<td><input type="submit" name="action" value="Save as Spam" /></td>
<td><input type="submit" name="action" value="Save as Ham" /></td>
</tr>
<tr>
<td><input type="submit" name="action" value="Delete from Spam" /></td>
<td><input type="submit" name="action" value="Delete from Ham" /></td>
</tr>
</table>
</form>
</div>

</div>

END;

if($time_start !== NULL) {

echo <<<END
<div>
<table border="0">
<tr><td>Memory used:     </td><td>$mem_used&thinsp;MB</td></tr>
<tr><td>Peak memory used:</td><td>$peak_mem_used&thinsp;MB</td></tr>
<tr><td>Time taken:      </td><td>$time_taken&thinsp;sec</td></tr>
</table>
</div>

END;

}

?>

</body>

</html>
