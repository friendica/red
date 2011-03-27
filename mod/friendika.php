<?php

function friendika_content(&$a) {

	$o = '';
	$o .= '<h3>Friendika</h3>';


	$o .= '<p></p><p>';

	$o .= 'View <a href="LICENSE">License</a>' . '<br /><br />';
	$o .= t('This is Friendika version') . ' ' . FRIENDIKA_VERSION . ' ';
	$o .= t('running at web location') . ' ' . $a->get_baseurl() . '</p><p>';

	$o .= t('Shared content within the Friendika network is provided under the <a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0 license</a>') . '</p><p>';

	$o .= t('Please visit <a href="http://project.friendika.com">Project.Friendika.com</a> to learn more about the Friendika project.') . '</p><p>';	

	$o .= t('Bug reports and issues: please visit') . ' ' . '<a href="http://bugs.friendika.com">Bugs.Friendika.com</a></p><p>';
	$o .= t('Suggestions, praise, donations, etc. - please email "Info" at Friendika - dot com') . '</p>';

	$o .= '<p></p>';

	if(count($a->plugins)) {
		$o .= '<p>' . t('Installed plugins/addons/apps') . '</p>';
		$o .= '<ul>';
		foreach($a->plugins as $p)
			if(strlen($p))
				$o .= '<li>' . $p . '</li>';
		$o .= '</ul>';
	}
	else
		$o .= '<p>' . t('No installed plugins/addons/apps');
 	
	return $o;











}