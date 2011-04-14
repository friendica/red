<?php
	

	class Template {
		var $s;
		var $r;
		var $search;
		var $replace;
		
		private function _build_replace($r, $prefix){
	
			if(is_array($r) && count($r)) {
				foreach ($r as $k => $v ) {
					if (is_array($v))
						$this->_build_replace($v, "$prefix$k.");
					
					$this->search[] =  $prefix . $k;
					$this->replace[] = $v;
				}
			}
		} 
		
		private function _replcb_if($m){
			//echo "<pre>"; var_dump($m);
			$keys = explode(".",$m[1]);
			$val = $this->r;
			foreach($keys as $k) {
				$val = $val[$k];
			}
			
			//echo $val;
			return ($val?$m[2]:"");
		}
		
		public function replace($s, $r) {
			$this->s = $s;
			$this->r = $r;
			$this->search = array();
			$this->replace = array();
	
			$this->_build_replace($r, "");
	
			
			$s = preg_replace_callback("|{{ *if *([^ }]*) *}}([^{]*){{ *endif *}}|", array($this, "_replcb_if"), $s);
			
			return str_replace($this->search,$this->replace,$s);
		}
		
	}	
	$t = new Template;
