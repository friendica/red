<?php /** @file */

function js_strings() {
	return replace_macros(get_markup_template('js_strings.tpl'), array(
		'$delitem'     => t('Delete this item?'),
		'$comment'     => t('Comment'),
		'$showmore'    => t('show more'),
		'$showfewer'   => t('show fewer'),
		'$divgrowmore' => t('+ Show More'),
		'$divgrowless' => t('- Show Less'),
		'$pwshort'     => t("Password too short"),
		'$pwnomatch'   => t("Passwords do not match"),
		'$everybody'   => t('everybody'),
		'$passphrase'  => t('Secret Passphrase'),
		'$passhint'    => t('Passphrase hint'),
		'$permschange' => t('Notice: Permissions have changed but have not yet been submitted.'),

		'$t01' => ((t('timeago.prefixAgo') != 'timeago.prefixAgo') ? t('timeago.prefixAgo') : ''),
		'$t02' => ((t('timeago.prefixFromNow') != 'timeago.prefixFromNow') ? t('timeago.prefixFromNow') : ''),
		'$t03' => t('ago'),
		'$t04' => t('from now'),
		'$t05' => t('less than a minute'),
		'$t06' => t('about a minute'),
		'$t07' => t('%d minutes'),
		'$t08' => t('about an hour'),
		'$t09' => t('about %d hours'),
		'$t10' => t('a day'),
		'$t11' => t('%d days'),
		'$t12' => t('about a month'),
		'$t13' => t('%d months'),
		'$t14' => t('about a year'),
		'$t15' => t('%d years'),
		'$t16' => t(' '), // wordSeparator
		'$t17' => ((t('timeago.numbers') != 'timeago.numbers') ? t('timeago.numbers') : '[]')

	));
}
