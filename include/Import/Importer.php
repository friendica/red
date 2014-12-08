<?php /** @file */

namespace RedMatrix\Import;

/**
 * @brief Class Import
 *
 * @package RedMatrix\Import
 */
class Import {

	private $credentials = null;

	protected $itemlist    = null;
	protected $src_items   = null;
	protected $items       = null;

	function get_credentials() {
		return $this->credentials;
	}

	function get_itemlist() {
		return $this->itemlist;
	}

	function get_item_ident($item) {

	}

	function get_item($item_ident) {

	}

	function get_taxonomy($item_ident) {

	}

	function get_children($item_ident) {

	}

	function convert_item($item_ident) {

	}

	function convert_taxonomy($item_ident) {

	}

	function convert_child($child) {

	}

	function store($item, $update = false) {

	}

	function run() {
		$this->credentials = $this->get_credentials();
		$this->itemlist = $this->get_itemlist();
		if($this->itemlist) {
			$this->src_items = array();
			$this->items = array();
			$cnt = 0;
			foreach($this->itemlist as $item) {
				$ident = $item->get_item_ident($item);
				$this->src_items[$ident]['item'] = $this->get_item($ident);
				$this->src_items[$ident]['taxonomy'] = $this->get_taxonomy($ident);
				$this->src_items[$ident]['children'] = $this->get_children($ident);
				$this->items[$cnt]['item'] = $this->convert_item($ident);
				$this->items[$cnt]['item']['term'] = $this->convert_taxonomy($ident);
				if($this->src_items[$ident]['children']) {
					$this->items[$cnt]['children'] = array();
					foreach($this->src_items[$ident]['children'] as $child) {
						$this[$cnt]['children'][] = $this->convert_child($child);
					}
				}
				$cnt ++;
			}
		}
	}
}