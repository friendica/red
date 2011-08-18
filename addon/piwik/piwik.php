<?php
/**
 * Name: Piwik Analytics
 * Description: Piwik Analytics Plugin for Friendika
 * Version: 1.0
 * Author: Tobias Diekershoff <https://diekershoff.homeunix.net/friendika/profile/tobias>
 */
 

/*   Piwik Analytics Plugin for Friendika
 *
 *   Author: Tobias Diekershoff
 *           tobias.diekershoff@gmx.net
 *
*   License: 3-clause BSD license
 *
 *   Configuration:
 *     Add the following two lines to your .htconfig.php file:
 *
 *     $a->config['piwik']['baseurl'] = 'www.example.com/piwik/';
 *     $a->config['piwik']['siteid'] = '1';
 *     $a->config['piwik']['optout'] = true;  // set to false to disable
 *
 *     Change the siteid to the ID that the Piwik tracker for your Friendika
 *     installation has. Alter the baseurl to fit your needs, don't care
 *     about http/https but beware to put the trailing / at the end of your
 *     setting.
 *
 *     Documentation see http://diekershoff.homeunix.net/redmine/wiki/friendikaplugin/Piwik_Plugin
 */

function piwik_install() {
	register_hook('page_end', 'addon/piwik/piwik.php', 'piwik_analytics');

        logger("installed piwik plugin");
}

function piwik_uninstall() {
	unregister_hook('page_end', 'addon/piwik/piwik.php', 'piwik_analytics');

        logger("uninstalled piwik plugin");
}

function piwik_analytics($a,&$b) {

	/*
	 *   styling of every HTML block added by this plugin is done in the
	 *   associated CSS file. We just have to tell Friendika to get it
	 *   into the page header.
	 */
	$a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/piwik/piwik.css' . '" media="all" />' . "\r\n";

	/*
	 *   Get the configuration variables from the .htconfig file.
	 */
	$baseurl = get_config('piwik','baseurl');
	$siteid  = get_config('piwik','siteid');
	$optout  = get_config('piwik','optout');

	/*
	 *   Add the Piwik code for the site.
	 */
	$b .= "<div id='piwik-code-block'> <!-- Piwik -->\r\n <script type=\"text/javascript\">\r\n var pkBaseURL = ((\"https:\" == document.location.protocol) ? \"https://".$baseurl."\" : \"http://".$baseurl."\");\r\n document.write(unescape(\"%3Cscript src='\" + pkBaseURL + \"piwik.js' type='text/javascript'%3E%3C/script%3E\"));\r\n </script>\r\n<script type=\"text/javascript\">\r\n try {\r\n var piwikTracker = Piwik.getTracker(pkBaseURL + \"piwik.php\", ".$siteid.");\r\n piwikTracker.trackPageView();\r\n piwikTracker.enableLinkTracking();\r\n }\r\n catch( err ) {}\r\n </script>\r\n<noscript><p><img src=\"http://".$baseurl."/piwik.php?idsite=".$siteid."\" style=\"border:0\" alt=\"\" /></p></noscript>\r\n <!-- End Piwik Tracking Tag --> </div>";
	/*
	 *   If the optout variable is set to true then display the notice
	 *   otherwise just include the above code into the page.
	 */
	if ($optout) {
            $b .= "<div id='piwik-optout-link'>";
            $b .= t("This website is tracked using the <a href='http://www.piwik.org'>Piwik</a> analytics tool.");
            $b .= " ";
            $the_url =  "http://".$baseurl ."index.php?module=CoreAdminHome&action=optOut";
            $b .= sprintf(t("If you do not want that your visits are logged this way you <a href='%s'>can set a cookie to prevent Piwik from tracking further visits of the site</a> (opt-out)."), $the_url);
            $b .= "</div>";
	}

}
function piwik_plugin_admin (&$a, &$o) {
    $t = file_get_contents( dirname(__file__)."/admin.tpl");
    $o = replace_macros( $t, array(
            '$submit' => t('Submit'),
            '$baseurl' => array('baseurl', t('Piwik Base URL'), get_config('piwik','baseurl' ), ''),
            '$siteid' => array('siteid', t('Site ID'), get_config('piwik','siteid' ), ''),
            '$optout' => array('optout', t('Show opt-out cookie link?'), get_config('piwik','optout' ), ''),
        ));
}
function piwik_plugin_admin_post (&$a) {
    $url = ((x($_POST, 'baseurl')) ? notags(trim($_POST['baseurl'])) : '');
    $id = ((x($_POST, 'siteid')) ? trim($_POST['siteid']) : '');
    $optout = ((x($_POST, 'optout')) ? trim($_POST['optout']) : '');
    set_config('piwik', 'baseurl', $url);
    set_config('piwik', 'siteid', $id);
    set_config('piwik', 'optout', $optout);
    info( t('Settings updated.'). EOL);
}
