<?php

namespace Kitsune\ClubPenguin;
use Kitsune;

class ClubPenguin extends Kitsune\Kitsune {

	private static $xml_handlers = array(
		"policy" => "handlePolicyRequest"
	);
	
	private function handlePolicyRequest($socket, $packet) {
		$this->penguins[$socket]->send("<cross-domain-policy><allow-access-from domain='*' to-ports='*' /></cross-domain-policy>");
	}
	
	protected function handleXmlPacket($socket, $packet) {
		if(array_key_exists($packet::$handler, self::$xml_handlers)) {
			$invokee = self::$xml_handlers[$packet::$handler];
			call_user_func(array($this, $invokee), $socket, $packet);
		} else {
			echo "Method for {$packet::$handler} not found!\n";
		}
	}
	
	protected function handleWorldPacket($socket, $packet) {
		echo "$packet::$handler\n", print_r($packet);
	}
	
}

?>