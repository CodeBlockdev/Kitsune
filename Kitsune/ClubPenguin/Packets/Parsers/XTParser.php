<?php

namespace Kitsune\ClubPenguin\Packets\Parsers;

class XTParser {

	public static function parse($xt_data) {
		$xt_array = explode('%', $xt_data);
		array_shift($xt_array);
		array_shift($xt_array);
		array_pop($xt_array);
		
		return $xt_array;
	}
	
}

?>