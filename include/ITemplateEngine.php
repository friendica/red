<?php
require_once 'boot.php';

/**
 * @brief Interface for template engines.
 */
interface ITemplateEngine {
	public function replace_macros($s, $v);
	public function get_markup_template($file, $root='');
}
