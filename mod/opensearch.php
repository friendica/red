<?php
    function opensearch_content(&$a) {
    	
		$tpl = load_view_file('view/opensearch.tpl');
	
		header("Content-type: application/opensearchdescription+xml");
	
		$o = replace_macros($tpl, array(
			'$baseurl' => $a->get_baseurl(),
			'$nodename' => $a->get_hostname(),
		));
		
		echo $o;
		
		killme();
		
	}
?>