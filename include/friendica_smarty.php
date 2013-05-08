<?php /** @file */
require_once 'include/ITemplateEngine.php';
require_once("library/Smarty/libs/Smarty.class.php");


class FriendicaSmarty extends Smarty {

	public $filename;

	function __construct() {
		parent::__construct();

		$a = get_app();
		$theme = current_theme();

		// setTemplateDir can be set to an array, which Smarty will parse in order.
		// The order is thus very important here
		$template_dirs = array('theme' => "view/theme/$theme/tpl/");
		if( x($a->theme_info,"extends") )
			$template_dirs = $template_dirs + array('extends' => "view/theme/".$a->theme_info["extends"]."/tpl/");
		$template_dirs = $template_dirs + array('base' => 'view/tpl/');
		$this->setTemplateDir($template_dirs);

		$this->setCompileDir('view/tpl/smarty3/compiled/');
		$this->setConfigDir('view/tpl/smarty3/config/');
		$this->setCacheDir('view/tpl/smarty3/cache/');

		$this->left_delimiter = $a->get_template_ldelim('smarty3');
		$this->right_delimiter = $a->get_template_rdelim('smarty3');

		// Don't report errors so verbosely
		$this->error_reporting = E_ALL & ~E_NOTICE;
	}

	function parsed($template = '') {
		if($template) {
			return $this->fetch('string:' . $template);
		}
		return $this->fetch('file:' . $this->filename);
	}
}



class FriendicaSmartyEngine implements ITemplateEngine {
	static $name ="smarty3";
	
	public function __construct(){
		if(!is_writable('view/tpl/smarty3/')){
			echo "<b>ERROR:</b> folder <tt>view/tpl/smarty3/</tt> must be writable by webserver."; killme();
		}
	}
	
	// ITemplateEngine interface
	public function replace_macros($s, $r) {
		$template = '';
		if(gettype($s) === 'string') {
			$template = $s;
			$s = new FriendicaSmarty();
		}
		foreach($r as $key=>$value) {
			if($key[0] === '$') {
				$key = substr($key, 1);
			}
			$s->assign($key, $value);
		}
		return $s->parsed($template);		
	}
	
	public function get_markup_template($file, $root=''){
		$template_file = theme_include($file, $root);
		if($template_file) {
			$template = new FriendicaSmarty();
			$template->filename = $template_file;

			return $template;
		}		
		return "";
	}
}