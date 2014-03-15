<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function filer_content(&$a) {

	if(! local_user()) {
		killme();
	}

	$term = unxmlify(trim($_GET['term']));
	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	logger('filer: tag ' . $term . ' item ' . $item_id);

	if($item_id && strlen($term)){
		// file item
		store_item_tag(local_user(),$item_id,TERM_OBJ_POST,TERM_FILE,$term,'');

		// protect the entire conversation from periodic expiration

		$r = q("select parent from item where id = %d and uid = %d limit 1",
			intval($item_id),
			intval(local_user())
		);
		if($r) {
			$x = q("update item set item_flags = ( item_flags | %d ) where id = %d and uid = %d limit 1",
				intval(ITEM_RETAINED),
				intval($r[0]['parent']),
				intval(local_user())
			);
		}
	} 
	else {
		$filetags = array();
		$r = q("select distinct(term) from term where uid = %d and type = %d order by term asc",
			intval(local_user()),
			intval(TERM_FILE)
		);
		if(count($r)) {
			foreach($r as $rr)
				$filetags[] = $rr['term'];
		}
		$tpl = get_markup_template("filer_dialog.tpl");
		$o = replace_macros($tpl, array(
			'$field' => array('term', t("Save to Folder:"), '', '', $filetags, t('- select -')),
			'$submit' => t('Save'),
		));
		
		echo $o;
	}
	killme();
}
