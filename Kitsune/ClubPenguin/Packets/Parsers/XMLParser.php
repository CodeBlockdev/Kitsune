<?php

namespace Kitsune\ClubPenguin\Packets\Parsers;

class XMLParser {

	public static function Parse($xml_data) {
		$xml_object = simplexml_load_string($xml_data, "SimpleXMLElement", LIBXML_NOCDATA);
		$xml_array = json_decode(json_encode($xml_object), true);
		
		return $xml_array;
	}
	
}

?>