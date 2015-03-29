<?php
/**
 * @file include/expire.php
 */

require_once('boot.php');
require_once('include/cli_startup.php');

function expire_run($argv, $argc){

	cli_startup();

	// perform final cleanup on previously delete items

	$r = q("select id from item where (item_restrict & %d) > 0 and (item_restrict & %d) = 0
		and changed < %s - INTERVAL %s",
		intval(ITEM_DELETED),
		intval(ITEM_PENDING_REMOVE),
		db_utcnow(), db_quoteinterval('10 DAY')
	);
	if ($r) {
		foreach ($r as $rr) {
			drop_item($rr['id'], false, DROPITEM_PHASE2);
		}
	}

	// physically remove anything that has been deleted for more than two months
	/** @FIXME - this is a wretchedly inefficient query */

	$r = q("delete from item where ( item_restrict & %d ) > 0 and changed < %s - INTERVAL %s",
		intval(ITEM_PENDING_REMOVE),
		db_utcnow(), db_quoteinterval('36 DAY')
	);

	/** @FIXME make this optional as it could have a performance impact on large sites */

	if (intval(get_config('system', 'optimize_items')))
		q("optimize table item");

	logger('expire: start', LOGGER_DEBUG);

	$site_expire = get_config('system', 'default_expire_days');

	logger('site_expire: ' . $site_expire);

	$r = q("SELECT channel_id, channel_address, channel_pageflags, channel_expire_days from channel where true");

	if ($r) {
		foreach ($r as $rr) {

			// expire the sys channel separately
			if ($rr['channel_pageflags'] & PAGE_SYSTEM)
				continue;

			// service class default (if non-zero) over-rides the site default

			$service_class_expire = service_class_fetch($rr['channel_id'], 'expire_days');
			if (intval($service_class_expire))
				$channel_expire = $service_class_expire;
			else
				$channel_expire = $site_expire;

			if (intval($channel_expire) && (intval($channel_expire) < intval($rr['channel_expire_days'])) ||
				intval($rr['channel_expire_days'] == 0)) {
				$expire_days = $channel_expire;
			} else {
				$expire_days = $rr['channel_expire_days'];
			}

			// if the site or service class expiration is non-zero and less than person expiration, use that
			logger('Expire: ' . $rr['channel_address'] . ' interval: ' . $expire_days, LOGGER_DEBUG);
			item_expire($rr['channel_id'], $expire_days);
		}
	}

	$x = get_sys_channel();
	if ($x) {

		// this should probably just fetch the channel_expire_days from the sys channel,
		// but there's no convenient way to set it.

		$expire_days = get_config('system', 'sys_expire_days');
		if ($expire_days === false)
			$expire_days = 30;

		if (intval($site_expire) && (intval($site_expire) < intval($expire_days))) {
			$expire_days = $site_expire;
		}

		logger('Expire: sys interval: ' . $expire_days, LOGGER_DEBUG);

		if ($expire_days)
			item_expire($x['channel_id'], $expire_days);

		logger('Expire: sys: done', LOGGER_DEBUG);
	}
}

if (array_search(__file__, get_included_files()) === 0){
	expire_run($argv, $argc);
	killme();
}
