<?php

function js_strings() {
	return replace_macros(get_markup_template('js_strings.tpl'), array(
		'$delitem'   => t('Delete this item?'),
		'$comment'   => t('Comment'),
		'$showmore'  => t('show more'),
		'$showfewer' => t('show fewer'),
		'$pwshort'   => t("Password too short"),
		'$pwnomatch' => t("Passwords do not match"),

		'$t01' => ((t('timeago.prefixAgo') != 'timeago.prefixAgo') ? t('timeago.prefixAgo') : 'null'),
		'$t02' => ((t('timeago.suffixAgo') != 'timeago.suffixAgo') ? t('timeago.suffixAgo') : 'null'),
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