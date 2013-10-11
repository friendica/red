<?php

function filestorage_content(&$a) {

	if(argc() > 1)
		$which = argv(1);
	else {
		notice( t('Requested profile is not available.') . EOL );
		$a->error = 404;
		return;
	}

        $r = q("select channel_id from channel where channel_address = '%s'",
                dbesc($which)
                );
               if($r) {
                $owner = intval($r[0]['channel_id']);
	}

        $observer = $a->get_observer();
        $ob_hash = (($observer) ? $observer['xchan_hash'] : '');

        $perms = get_all_perms($owner,$ob_hash);

        if(! $perms['view_storage']) {
                notice( t('Permission denied.') . EOL);
                return;
        }

//	Since we have ACL'd files in the wild, but don't have ACL here yet, we 
//	need to return for anoyne other than the owner, despite the perms check for now.

	$is_owner = (((local_user()) && ($owner  == local_user())) ? true : false);
	if (! $is_owner) {
		 info( t('Permission Denied.') . EOL );
	return;
	}

// 	TODO This will also need to check for files on disk and delete them from there as well as the DB.
	if ((argc() > 3 && argv(3) === 'delete') ? true : false);{
	        if(! $perms['write_storage']) {
        	        notice( t('Permission denied.  VS.') . EOL);
                return;
		}

		 $file = argv(2);
		 $r = q("delete from attach where id = '%s' and uid = '%s' limit 1",
			dbesc($file),
			intval($owner)
		);


	}	


$r = q("select * from attach where uid = %d order by filename asc",
	intval($owner)
);

		$files = null;

		if($r) {
			$files = array();
			foreach($r as $rr) {
				$files[$rr['id']][] = array('id' => $rr['id'],'download' => $rr['hash'], 'title' => $rr['filename'], 'size' => $rr['filesize']);
			} 
		}


	$limit = service_class_fetch ($owner,'attach_upload_limit'); 
		$r = q("select sum(filesize) as total from attach where uid = %d ",
		intval($owner)
	);
	$used = $r[0]['total'];

		$url = z_root() . "/filestorage/" . $which; 
       return $o . replace_macros(get_markup_template("filestorage.tpl"), array(
		'$baseurl' => $url,
		'$download' => t('Download'),
		'$files' => $files,
		'$channel' => $which,
		'$delete' => t('Delete'),
		'$used' => $used,
		'$usedlabel' => t('Used: '),
		'$limit' => $limit,
		'$limitlabel' => t('Limit: '),
        ));
    

}
