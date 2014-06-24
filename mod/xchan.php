<?php


function xchan_content(&$a) {


	$o .= '<h3>Xchan Lookup</h3>';

	$o .= '<form action="xchan" method="get">';
	$o .= 'Lookup xchan beginning with: <input type="text" style="width: 250px;" name="addr" value="' . $_GET['addr'] .'" />';
	$o .= '<input type="submit" name="submit" value="Submit" /></form>'; 

	$o .= '<br /><br />';

	if(x($_GET,'addr')) {
		$addr = trim($_GET['addr']);

		$r = q("select xchan_name from xchan where xchan_hash like '%s%%'",
			dbesc($addr)
		);

		if($r) {
			foreach($r as $rr)
				$o .= $rr['xchan_name'] . EOL;
		}
		else
			notice( t('Not found.') . EOL);
	}	
	return $o;
}
