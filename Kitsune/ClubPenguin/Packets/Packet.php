<?php

namespace Kitsune\ClubPenguin\Packets;
use Kitsune\ClubPenguin\Packets\Parsers;

class Packet {

	public static $is_xml;
	public static $extension;
	public static $handler;
	public static $data;
	
	public function __construct($raw_data) {
		$first_character = substr($raw_data, 0, 1);
		self::$is_xml = $first_character == '<';
		if(self::$is_xml) {
			$xml_array = Parsers\XMLParser::parse($raw_data);
			if(!$xml_array) {
				self::$handler = "policy";
			} else {
				self::$handler = $xml_array["body"]["@attributes"]["action"];
				self::$data = $xml_array;
			}
		} else {
			$xt_array = Parsers\XTParser::parse($raw_data);
			self::$extension = $xt_array[0];
			self::$handler = $xt_array[1];
			array_shift($xt_array);
			array_shift($xt_array);
			
			self::$data = $xt_array;
		}
	}
	
}