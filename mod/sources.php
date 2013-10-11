<?php /** @file */

function sources_post(&$a) {
	if(! local_user())
		return;

	if(! feature_enabled(local_user(),'channel_sources'))
		return '';

	$source = intval($_REQUEST['source']);
	$xchan = $_REQUEST['xchan'];
	$words = $_REQUEST['words'];
	$frequency = $_REQUEST['frequency'];

	$channel = $a->get_channel();


	if(! $source) {
		$r = q("insert into source ( src_channel_id, src_channel_xchan, src_xchan, src_patt )
			values ( %d, '%s', '%s', '%s' ) ",
			intval(local_user()),
			dbesc($channel['channel_hash']),
			dbesc($xchan),
			dbesc($words)
		);
		if($r) {
			info( t('Source created.') . EOL);
		}
		goaway(z_root() . '/sources');
	}
	else {
		$r = q("update source set src_xchan = '%s', src_patt = '%s' where src_channel_id = %d and src_id = %d limit 1",
			dbesc($xchan),
			dbesc($words),
			intval(local_user()),
			intval($source)
		);
		if($r) {
			info( t('Source updated.') . EOL);
		}
		
	}
}


function sources_content(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return '';
	}

	if(! feature_enabled(local_user(),'channel_sources')) {
		return '';
	} 

	// list sources
	if(argc() == 1) {
		$r = q("select source.*, xchan.* from source left join xchan on src_xchan = xchan_hash where src_channel_id = %d",
			intval(local_user())
		);
		if($r) {
			for($x = 0; $x < count($r); $x ++) {
				$r[$x]['src_patt'] = htmlspecialchars($r[$x]['src_patt'], ENT_COMPAT,'UTF-8');
			}
		}
		$o = replace_macros(get_markup_template('sources_list.tpl'), array(
			'$title' => t('Channel Sources'),
			'$desc' => t('Manage remote sources of content for your channel.'),
			'$new' => t('New Source'),
			'$sources' => $r
		));
		return $o;
	}

	if(argc() == 2 && argv(1) === 'new') {
		// TODO add the words 'or RSS feed' and corresponding code to manage feeds and frequency

		$o = replace_macros(get_markup_template('sources_new.tpl'), array(
			'$title' => t('New Source'),
			'$desc' => t('Import all or selected content from the following channel into this channel and distribute it according to your channel settings.'),
			'$words' => array( 'words', t('Only import content with these words (one per line)'),'',t('Leave blank to import all public content')),
			'$name' => array( 'name', t('Channel Name'), '', ''),
			'$submit' => t('Submit')
		));
		return $o;

	}

	if(argc() == 2 && intval(argv(1))) {
		// edit source
		$r = q("select source.*, xchan.* from source left join xchan on src_xchan = xchan_hash where src_id = %d and src_channel_id = %d limit 1",
			intval(argv(1)),
			intval(local_user())
		);
		if(! $r) {
			notice( t('Source not found.') . EOL);
			return '';
		}

		$r[0]['src_patt'] = htmlspecialchars($r[0]['src_patt'], ENT_QUOTES,'UTF-8');

		$o = replace_macros(get_markup_template('sources_edit.tpl'), array(
			'$title' => t('Edit Source'),
			'$drop' => t('Delete Source'),
			'$id' => $r[0]['src_id'],
			'$desc' => t('Import all or selected content from the following channel into this channel and distribute it according to your channel settings.'),
			'$words' => array( 'words', t('Only import content with these words (one per line)'),$r[0]['src_patt'],t('Leave blank to import all public content')),
			'$xchan' => $r[0]['src_xchan'],
			'$name' => array( 'name', t('Channel Name'), $r[0]['xchan_name'], ''),
			'$submit' => t('Submit')
		));
		return $o;

	}

	if(argc() == 3 && intval(argv(1)) && argv(2) === 'drop') {
		$r = q("select * from source where src_id = %d and src_channel_id = %d limit 1",
			intval(argv(1)),
			intval(local_user())
		);
		if(! $r) {
			notice( t('Source not found.') . EOL);
			return '';
		}
		$r = q("delete from source where src_id = %d and src_channel_id = %d limit 1",
			intval(argv(1)),
			intval(local_user())
		);
		if($r)
			info( t('Source removed') . EOL);
		else
			notice( t('Unable to remove source.') . EOL);

		goaway(z_root() . '/sources');

	}

	// shouldn't get here.

}