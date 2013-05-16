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

        $basecompiledir = $a->config['system']['smarty3_folder'];
        
		$this->setCompileDir($basecompiledir.'/compiled/');
		$this->setConfigDir($basecompiledir.'/config/');
		$this->setCacheDir($basecompiledir.'/cache/');

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
        $a = get_app();
        $basecompiledir = $a->config['system']['smarty3_folder'];
        if (!$basecompiledir) $basecompiledir = dirname(__dir__)."/view/tpl/smarty3";
        if (!is_dir($basecompiledir)) {
            echo "<b>ERROR:</b> folder <tt>$basecompiledir</tt> does not exist."; killme();
        }
		if(!is_writable($basecompiledir)){
			echo "<b>ERROR:</b> folder <tt>$basecompiledir</tt> must be writable by webserver."; killme();
		}
         $a->config['system']['smarty3_folder'] = $basecompiledir;
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

	public function get_intltext_template($file, $root='') {
		$a = get_app();
    
		if(file_exists("view/{$a->language}/$file"))
        	$template_file = "view/{$a->language}/$file";
	    elseif(file_exists("view/en/$file"))
        	$template_file = "view/en/$file";
    	else
        	$template_file = theme_include($file,$root);
		if($template_file) {
			$template = new FriendicaSmarty();
			$template->filename = $template_file;

			return $template;
		}		
		return "";
	}



}
