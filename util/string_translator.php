<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<style>
	textarea { width: 100% }
	.no { background: #ffdddd; }
	label { border-bottom: 1px solid #888; }
</style>
</head>
<body>	
<?php

$FRIENDIKA_PATH = dirname(dirname(__FILE__));

/* find languages */
$LANGS=array();
$d = dir($FRIENDIKA_PATH."/view");
while (false !== ($entry = $d->read())) {
	if (is_file($d->path."/".$entry."/strings.php")){
		$LANGS[] = $entry;
	}

}
$d->close();


class A{
	var $strings = Array();
}

function loadstrings($lang = NULL){
	global $FRIENDIKA_PATH;
	if (is_null($lang)) {
		$path = $FRIENDIKA_PATH."/util/strings.php";
	} else {
		$path = $FRIENDIKA_PATH."/view/$lang/strings.php";
	}
	$a = new A();
	include_once($path);
	return $a->strings;
}


function savestrings($lang, $strings){
	global $FRIENDIKA_PATH;
	$path = $FRIENDIKA_PATH."/view/$lang/strings.php";
	$f = fopen($path,"w");
	fwrite($f, "<"); fwrite($f, "?php\n");
	foreach($strings as $k=>$v){
	     $k=str_replace("'","\'", $k);
   	     $k=str_replace("\\\\'","\'", $k);
   	     $k=str_replace("\n","\\n", $k);
   	     $k=str_replace("\r","\\r", $k);
	     $v=str_replace("'","\'", $v);
	     $v=str_replace("\\\\'","\'", $v);
         $v=str_replace("\n","\\n", $v);
   	     $v=str_replace("\r","\\r", $v);

		 fwrite( $f, '$a->strings[\''.$k.'\'] = \''. $v .'\';'."\n" );
		 #echo '$a->strings[\''.$k.'\'] = \''. $v .'\''."\n" ;
	}
    fwrite($f, "?"); fwrite($f, ">\n");
	fclose($f);
}



function hexstr($hexstr) {
  $hexstr = str_replace(' ', '', $hexstr);
  $hexstr = str_replace('\x', '', $hexstr);
  $retstr = pack('H*', $hexstr);
  return $retstr;
}

function strhex($string) {
  $hexstr = unpack('H*', $string);
  return array_shift($hexstr);
}


echo "<h1>Translator</h1>";
echo "<small>Utility to translate <code>string.php</code> file.";
echo " Need write permission to language file you want to modify</small>";
echo "<p>Installed languages:";
foreach($LANGS as $l){
	echo "<a href='?lang=$l'>$l</a>, ";
}
echo "</p>";


$strings['en'] = loadstrings();

if (isset($_GET['lang'])){

	$lang = $_GET['lang'];
	$strings[$lang] = loadstrings($lang);
	
	$n1 = count($strings['en']);
	$n2 = count($strings[$lang]);
	
	echo "<pre>";
	echo "Tranlsate en to $lang<br>";
	//echo "Translated $n2 over $n1 strings<br>";
	echo "</pre><hr/>";



	if (isset($_POST['save'])){
		echo "saving...";
		foreach ($_POST as $k=>$v){
			if ($k!="save" && $k!="from"){
			    $k=hexstr($k);
				$strings[$lang][$k] = $v;
			}
		}
		savestrings($lang, $strings[$lang]);
		echo "ok.<br>";
	}





	if (!isset($_POST['from'])){
		$from=0;
	} else {
		$from = $_POST['from'];
		if ($_POST['save']=="Next")
			$from += 10;
		if ($_POST['save']=="Prev")
			$from -= 10;
	}
	$count = count($strings['en']);
	$len = 10;
	if ($from+$len>$count) $len=$count-$from;
	$thestrings = array_slice($strings['en'], $from, $len, true);
	

	
	echo "<form method='POST'>";
	
	if ($from>0)
    echo "<input type='submit' name='save' id='save' value='Prev'/>";
  echo "<input type='submit' name='reload' id='reload' value='Reload'/>";   
  if ($from+$len<$count)
    echo "<input type='submit' name='save' id='save' value='Next'/>";
	
	foreach($thestrings as $k=>$v){
		$id = strhex($k);
		$translation = $strings[$lang][$k];
		
		$v=str_replace("\n","\\n", $v);
   	    $v=str_replace("\r","\\r", $v);
		$translation=str_replace("\n","\\n", $translation);
   	    $translation=str_replace("\r","\\r", $translation);
		
		$istranslate = $translation != '' ? 'yes':'no';
		echo "<dl class='$istranslate'>";
		echo "<dt><pre><label for='$id'>".htmlspecialchars($v)."</label></pre></dt>";
		echo "<dd><textarea id='$id' name='$id'>$translation</textarea></dd>";
		echo "</dl>";
	}
	
	
	echo "<input type='hidden' name='from' value='$from'/>";	

	if ($from>0)
		echo "<input type='submit' name='save' id='save' value='Prev'/>";
	echo "<input type='submit' name='reload' id='reload' value='Reload'/>";		
	if ($from+$len<$count)
		echo "<input type='submit' name='save' id='save' value='Next'/>";

	echo "</form>";
	
	
}
?>
</body>
</html>

