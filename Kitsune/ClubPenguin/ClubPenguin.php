<?php

namespace Kitsune\ClubPenguin;
use Kitsune;

abstract class ClubPenguin extends Kitsune\Kitsune {

	private static $xml_handlers = array(
		"policy" => "handlePolicyRequest",
		"verChk" => "handleVersionCheck",
		"rndK" => "handleRandomKey",
		"login" => "handleLogin"
	);
	
	protected $world_handlers = array(
		// Overridden in the World class
	);
	
	private function handlePolicyRequest($socket, $packet) {
		$this->penguins[$socket]->send("<cross-domain-policy><allow-access-from domain='*' to-ports='*' /></cross-domain-policy>");
	}
	
	private function handleVersionCheck($socket, $packet) {
		$this->penguins[$socket]->send("<msg t='sys'><body action='apiOK' r='0'></body></msg>");
	}
	
	private function handleRandomKey($socket, $packet) {
		$penguin = $this->penguins[$socket];
		$penguin->random_key = Hashing::generateRandomKey();
		$penguin->send("<msg t='sys'><body action='rndK' r='-1'><k>" . $penguin->random_key . "</k></body></msg>");
	}
	
	abstract protected function handleLogin($socket, $packet);
	
	protected function handleXmlPacket($socket, $packet) {
		if(array_key_exists($packet::$handler, self::$xml_handlers)) {
			$invokee = self::$xml_handlers[$packet::$handler];
			call_user_func(array($this, $invokee), $socket, $packet);
		} else {
			echo "Method for {$packet::$handler} not found!\n";
		}
	}
	
	protected function handleWorldPacket($socket, $packet) {
		if($this->penguins[$socket]->identified == true) {
			if(isset($this->world_handlers[$packet::$extension])) {
				if(!empty($this->world_handlers[$packet::$extension])) {
					if(isset($this->world_handlers[$packet::$extension][$packet::$handler])) {
						if(method_exists($this, $this->world_handlers[$packet::$extension][$packet::$handler])) {
							call_user_func(array($this, $this->world_handlers[$packet::$extension][$packet::$handler]), $socket, $packet);
						} else {
							echo "Method for {$packet::$extension}%{$packet::$handler} is un-callable!\n";
						}
					} else {
						echo "Method for {$packet::$extension}%{$packet::$handler} doesn't exist/has not been set\n";
					}
				} else {
					echo "There are no handlers for {$packet::$extension}\n";
				}
			} else {
				echo "The packet extension '{$packet::$extension}' is not handled\n";
			}
		} else {
			$this->removePenguin($this->penguins[$socket]);
		}
	}
	
}

?>