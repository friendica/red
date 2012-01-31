<?php

#   Copyright (C) 2010 Tobias Leupold <tobias.leupold@web.de>
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

echo <<<END
<?xml version="1.0" encoding="UTF-8"?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
   "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>

<title>b8 Berkeley DB setup</title>

<meta http-equiv="content-type" content="text/html; charset=UTF-8" />

<meta name="dc.creator" content="Tobias Leupold" />
<meta name="dc.rights" content="Copyright (c) by Tobias Leupold" />

</head>

<body>

<div>

<h1>b8 Berkeley DB setup</h1>


END;

$failed = FALSE;

if(isset($_POST['handler'])) {

	$dbfile = $_POST['dbfile'];
	$dbfile_directory = $_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']);

	echo "<h2>Creating database</h2>\n\n";

	echo "<p>\n";

	echo "Checking database file name &hellip; ";

	if($dbfile == "") {
		echo "<span style=\"color:red;\">Please provide the name of the database file!</span><br />\n";
		$failed = TRUE;
	}
	else
		echo "$dbfile<br />\n";

	if(!$failed) {

		echo "Touching/Creating " . htmlentities($dbfile) . " &hellip; ";

		if(touch($dbfile) === FALSE) {
			echo "<span style=\"color:red;\">Failed to touch the database file. Please check the given filename and/or fix the permissions of $dbfile_directory.</span><br />\n";
			$failed = TRUE;
		}
		else
			echo "done<br />\n";

	}

	if(!$failed) {

		echo "Setting file permissions to 0666 &hellip ";

		if(chmod($dbfile, 0666) === FALSE) {
			echo "<span style=\"color:red;\">Failed to change the permissions of $dbfile_directory/$dbfile. Please adjust them manually.</span><br />\n";
			$failed = TRUE;
		}
		else
			echo "done<br />\n";

	}

	if(!$failed) {

		echo "Checking if the given file is empty &hellip ";

		if(filesize($dbfile) > 0) {
			echo "<span style=\"color:red;\">$dbfile_directory/$dbfile is not empty. Can't create a new database. Please delete/empty this file or give another filename.</span><br />\n";
			$failed = TRUE;
		}
		else
			echo "it is<br />\n";

	}

	if(!$failed) {

		echo "Connecting to $dbfile &hellip; ";

		$db = dba_open($dbfile, "c", $_POST['handler']);

		if($db === FALSE) {
			echo "<span style=\"color:red;\">Could not connect to the database!</span><br />\n";
			$failed = TRUE;
		}
		else
			echo "done<br />\n";

	}

	if(!$failed) {

		echo "Storing necessary internal variables &hellip ";

		$internals = array(
			"bayes*dbversion" => "2",
			"bayes*texts.ham" => "0",
			"bayes*texts.spam" => "0"
		);

		foreach($internals as $key => $value) {
			if(dba_insert($key, $value, $db) === FALSE) {
				echo "<span style=\"color:red;\">Failed to insert data!</span><br />\n";
				$failed = TRUE;
				break;
			}
		}

		if(!$failed)
			echo "done<br />\n";

	}

	if(!$failed) {

		echo "Trying to read data from the database &hellip ";

		$dbversion = dba_fetch("bayes*dbversion", $db);

		if($dbversion != "2") {
			echo "<span style=\"color:red;\">Failed to read data!</span><br />\n";
			$failed = TRUE;
		}
		else
			echo "success<br />\n";
	}

	if(!$failed) {

		dba_close($db);

		echo "</p>\n\n";
		echo "<p style=\"color:green;\">Successfully created a new b8 database!</p>\n\n";
		echo "<table>\n";
		echo "<tr><td>Filename:</td><td>$dbfile_directory/$dbfile</td></tr>\n";
		echo "<tr><td>DBA handler:</td><td>{$_POST['handler']}</td><tr>\n";
		echo "</table>\n\n";
		echo "<p>Move this file to it's destination directory (default: the base directory of b8) to use it with b8. Be sure to use the right DBA handler in b8's configuration.";

	}

	echo "</p>\n\n";

}

if($failed === TRUE or !isset($_POST['handler'])) {

echo <<<END
<form action="{$_SERVER['PHP_SELF']}" method="post">

<h2>DBA Handler</h2>

<p>
The following table shows all available DBA handlers. Please choose the "Berkeley DB" one.
</p>

<table>
<tr><td></td><td><b>Handler</b></td><td><b>Description</b></td></tr>

END;

foreach(dba_handlers(TRUE) as $name => $version) {

	$checked = "";

	if(!isset($_POST['handler'])) {
		if(strpos($version, "Berkeley") !== FALSE )
			$checked = " checked=\"checked\"";
	}
	else {
		if($_POST['handler'] == $name)
			$checked = " checked=\"checked\"";
	}

	echo "<tr><td><input type=\"radio\" name=\"handler\" value=\"$name\"$checked /></td><td>$name</td><td>$version</td></tr>\n";

}

echo <<<END
</table>

<h2>Database file</h2>

<p>
Please the name of the desired database file. It will be created in the directory where this script is located.
</p>

<p>
<input type="text" name="dbfile" value="wordlist.db" />
</p>

<p>
<input type="submit" value="Create the database" />
</p>

</form>


END;

}

?>

</div>

</body>

</html>
