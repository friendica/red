<?php
/**
 * Name: Converter App
 * Description: Unit converter application
 * Version: 1.0
 * Author: Mike Macgirvin <http://macgirvin.com/profile/mike>
 */

function convert_install() {
	register_hook('app_menu', 'addon/convert/convert.php', 'convert_app_menu');
}

function convert_uninstall() {
	unregister_hook('app_menu', 'addon/convert/convert.php', 'convert_app_menu');
}

function convert_app_menu($a,&$b) {
	$b['app_menu'] .= '<div class="app-title"><a href="convert">Units Conversion</a></div>'; 
}


function convert_module() {}







function convert_content($app) {

include("UnitConvertor.php");
 
 class TP_Converter extends UnitConvertor {
	function TP_Converter($lang = "en")
	{
		if ($lang != 'en' ) {
			$dec_point = '.'; $thousand_sep = "'";
		} else {
			$dec_point = '.'; $thousand_sep = ",";
		}
		
		$this->UnitConvertor($dec_point , $thousand_sep );

	} // end func UnitConvertor

	function find_base_unit($from,$to) {
		while (list($skey,$sval) = each($this->bases)) {
				if ($skey == $from || $to == $skey || in_array($to,$sval) || in_array($from,$sval)) {
					return $skey;				
				}
		}
		return false;		
	}

	function getTable($value, $from_unit, $to_unit, $precision) {
	
		if ($base_unit = $this->find_base_unit($from_unit,$to_unit)) {
		
			// A baseunit was found now lets convert from -> $base_unit 
			
				$cell ['value'] = $this->convert($value, $from_unit, $base_unit, $precision)." ".$base_unit;	
				$cell ['class']	= ($base_unit == $from_unit || $base_unit == $to_unit) ? "framedred": "";
				$cells[] = $cell;
			// We now have the base unit and value now lets produce the table;
			while (list($key,$val) = each($this->bases[$base_unit])) {
				$cell ['value'] = $this->convert($value, $from_unit, $val, $precision)." ".$val;	
				$cell ['class']	= ($val == $from_unit || $val == $to_unit) ? "framedred": "";
				$cells[] = $cell;
			}

			$cc = count($cells);
			$string = "<table class=\"framed grayish\" border=\"1\" cellpadding=\"5\" width=\"80%\" align=\"center\"><tr>";
			$string .= "<td rowspan=\"$cc\" align=\"center\">$value $from_unit</td>";
			$i=0;
			foreach ($cells as $cell) {
				if ($i==0) {
					$string .= "<td class=\"".$cell['class']."\">".$cell['value']."</td>";
					$i++;
				} else {
					$string .= "</tr><tr><td class=\"".$cell['class']."\">".$cell['value']."</td>";
				}
			}
			$string .= "</tr></table>";
			return $string;
		}		
		
	}
}


$conv = new TP_Converter('en');


$conversions = array(
	'Temperature'=>array('base' =>'Celsius',
		'conv'=>array(
			'Fahrenheit'=>array('ratio'=>1.8, 'offset'=>32),
			'Kelvin'=>array('ratio'=>1, 'offset'=>273),
			'Reaumur'=>0.8
		)
	),
	'Weight' => array('base' =>'kg',
		'conv'=>array(
			'g'=>1000,
			'mg'=>1000000,
			't'=>0.001,
			'grain'=>15432,
			'oz'=>35.274,
			'lb'=>2.2046,
			'cwt(UK)'	=> 0.019684,
			'cwt(US)'	=> 0.022046, 
			'ton (US)'	=> 0.0011023,
			'ton (UK)'	=> 0.0009842
		)
	),
	'Distance' => array('base' =>'km',
		'conv'=>array(
			'm'=>1000,
			'dm'=>10000,
			'cm'=>100000,
			'mm'=>1000000,
	 		'mile'=>0.62137,
			'naut.mile'=>0.53996,
	 		'inch(es)'=>39370,
			'ft'=>3280.8,
			'yd'=>1093.6,
			'furlong'=>4.970969537898672,
			'fathom'=>546.8066491688539
		)
	),
	'Area' => array('base' =>'km 2',
		'conv'=>array(	
			'ha'=>100,
			'acre'=>247.105,
			'm 2'=>pow(1000,2),
			'dm 2'=>pow(10000,2),
			'cm 2'=>pow(100000,2),
			'mm 2'=>pow(1000000,2), 
			'mile 2'=>pow(0.62137,2),
			'naut.miles 2'=>pow(0.53996,2),
	 		'in 2'=>pow(39370,2),
			'ft 2'=>pow(3280.8,2),
			'yd 2'=>pow(1093.6,2),
		)
	),
	'Volume' => array('base' =>'m 3',
		'conv'=>array(
			'in 3'=>61023.6,
			'ft 3'=>35.315,
			'cm 3'=>pow(10,6),
	 		'dm 3'=>1000,
			'litre'=>1000,
			'hl'=>10,
			'yd 3'=>1.30795,
	 		'gal(US)'=>264.172,
			'gal(UK)'=>219.969,
			'pint' => 2113.376,
			'quart' => 1056.688,
			'cup' => 4266.753,
			'fl oz' => 33814.02,
			'tablespoon' => 67628.04,
			'teaspoon' => 202884.1,
			'pt (UK)'=>1000/0.56826, 
			'barrel petroleum'=>1000/158.99,
			'Register Tons'=>2.832, 
			'Ocean Tons'=>1.1327
		)
	),
	'Speed'	=>array('base' =>'kmph',
		'conv'=>array(
			'mps'=>0.0001726031,
			'milesph'=>0.62137,
			'knots'=>0.53996,
			'mach STP'=>0.0008380431,
			'c (warp)'=>9.265669e-10
		)
	)
);


while (list($key,$val) = each($conversions)) {
	$conv->addConversion($val['base'], $val['conv']);
	$list[$key][] = $val['base'];
	while (list($ukey,$uval) = each($val['conv'])) {
		$list[$key][] = $ukey;
	}
}

  $o .= '<h3>Unit Conversions</h3>';


	if (isset($_POST['from_unit']) && isset($_POST['value'])) {
    	$_POST['value'] = $_POST['value'] + 0;


		$o .= ($conv->getTable($_POST['value'], $_POST['from_unit'], $_POST['to_unit'], 5))."</p>";
	} else {
		$o .= "<p>Select:</p>";
	}

	if(isset($_POST['value']))
		$value = $_POST['value'];
	else
		$value = '';

	$o .= '<form action="convert" method="post" name="conversion">';
    $o .= '<input name="value" type="text" id="value" value="' . $value . '" size="10" maxlength="10" />';
    $o .= '<select name="from_unit" size="12">';



	reset($list);
	while(list($key,$val) = each($list)) {
		$o .=  "\n\t<optgroup label=\"$key\">";
		while(list($ukey,$uval) = each($val)) {
			$selected = (($uval == $_POST['from_unit']) ? ' selected="selected" ' : '');
			$o .=  "\n\t\t<option value=\"$uval\" $selected >$uval</option>";
		}
		$o .= "\n\t</optgroup>";
	}

	$o .= '</select>';

    $o .= '<input type="submit" name="Submit" value="Submit" /></form>';
  
	return $o;
}
