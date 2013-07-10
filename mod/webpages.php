<?php
function webpages_content(&$a) {


$r = q("select * from item_id");

//print "<br /> <br /> <br /> <br />";
//foreach ($r as $rr) {
//print '<a href="editwebpage/' . ($rr['iid']) .'">Edit</a>' . '&nbsp' . ($rr['sid']) . '<br />';
//}

		$pages = null;

		if($r) {
			$pages = array();
			foreach($r as $rr) {
				$pages[$rr['iid']][] = array('url' => $rr['iid'],'title' => $rr['sid']);
			} 
		}

		logger('mod_profile: things: ' . print_r($pages,true), LOGGER_DATA); 

       return replace_macros(get_markup_template("webpageslist.tpl"), array(
           	    '$webpages' => $webpages
        ));
    }


return;
}