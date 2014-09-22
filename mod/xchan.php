<?php


function xchan_content(&$a) {


	$o .= '<h3>Xchan Lookup</h3>';

	$o .= '<form action="xchan" method="get">';
	$o .= 'Lookup xchan beginning with (or webbie): <input type="text" style="width: 250px;" name="addr" value="' . $_GET['addr'] .'" />';
	$o .= '<input type="submit" name="submit" value="Submit" /></form>'; 

	$o .= '<br /><br />';

	if(x($_GET,'addr')) {
		$addr = trim($_GET['addr']);

		$r = q("select * from xchan where xchan_hash like '%s%%' or xchan_addr = '%s' group by xchan_hash",
			dbesc($addr),
			dbesc($addr)
		);

		if($r) {
			foreach($r as $rr) {
				$o .= str_replace(array("\n"," "),array("<br/>","&nbsp;"),print_r($rr,true)) . EOL;

				$s = q("select * from hubloc where hubloc_hash like '%s'",
					dbesc($r[0]['xchan_hash'])
				);

				if($s) {
					foreach($s as $rr)
						$o .= str_replace(array("\n"," "),array("<br/>","&nbsp;"),print_r($rr,true)) . EOL;
				}
			}
		}
		else
			notice( t('Not found.') . EOL);

	}	
	return $o;
}
