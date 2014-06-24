<?php


/**
* @package util
*/

#require_once('boot.php');
#require_once('include/cli_startup.php');
require_once "library/Smarty/libs/Smarty.class.php";

#cli_startup();

$folders = array_merge(array('view/tpl/'),glob('view/theme/*/tpl/*',GLOB_ONLYDIR));

$s = new Smarty();

$s->setTemplateDir($folders);

$s->setCompileDir('view/tpl/smarty3/compiled/');
$s->setConfigDir('view/tpl/smarty3/config/');
$s->setCacheDir('view/tpl/smarty3/cache/');

$s->left_delimiter = "{{";
$s->right_delimiter = "}}";

$s->compileAllTemplates('.tpl',true);