<?php


function format_notification($item) {


return;

// convert this logic into a json array just like the system notifications

	switch($item['verb']){
		case ACTIVITY_LIKE:
			

				$notif_content .= replace_macros($tpl_item_likes,array(
							'$itemem_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$item['parent'],
							'$itemem_image' => $item['author-avatar'],
							'$itemem_text' => sprintf( t("%s liked %s's post"), $item['author-name'], $item['pname']),
							'$itemem_when' => relative_date($item['created'])
						));
						break;
						
					case ACTIVITY_DISLIKE:
						$notif_content .= replace_macros($tpl_item_dislikes,array(
							'$itemem_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$item['parent'],
							'$itemem_image' => $item['author-avatar'],
							'$itemem_text' => sprintf( t("%s disliked %s's post"), $item['author-name'], $item['pname']),
							'$itemem_when' => relative_date($item['created'])
						));
						break;
						
					case ACTIVITY_FRIEND:
					
						$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
						$obj = parse_xml_string($xmlhead.$item['object']);
						$item['fname'] = $obj->title;
						
						$notif_content .= replace_macros($tpl_item_friends,array(
							'$itemem_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$item['parent'],
							'$itemem_image' => $item['author-avatar'],
							'$itemem_text' => sprintf( t("%s is now friends with %s"), $item['author-name'], $item['fname']),
							'$itemem_when' => relative_date($item['created'])
						));
						break;
						
					default:
						$itemem_text = (($item['id'] == $item['parent'])
							? sprintf( t("%s created a new post"), $item['author-name'])
							: sprintf( t("%s commented on %s's post"), $item['author-name'], $item['pname']));
						$tpl = (($item['id'] == $item['parent']) ? $tpl_item_posts : $tpl_item_comments);

						$notif_content .= replace_macros($tpl,array(
							'$itemem_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$item['parent'],
							'$itemem_image' => $item['author-avatar'],
							'$itemem_text' => $itemem_text,
							'$itemem_when' => relative_date($it['created'])
						));
				}
}

