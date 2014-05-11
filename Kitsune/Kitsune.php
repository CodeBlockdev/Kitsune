<?php

namespace Kitsune;
use Kitsune\ClubPenguin;
use Kitsune\ClubPenguin\Packets;

abstract class Kitsune extends Spirit {

	protected $penguins = array();
	
	protected function handleAccept($socket) {
		$new_penguin = new ClubPenguin\Penguin($socket);
		$this->penguins[$socket] = $new_penguin;
	}
	
	protected function handleReceive($socket, $data) {
		echo "$data\n";
		$chunked_array = explode("\0", $data);
		array_pop($chunked_array);
		
		foreach($chunked_array as $raw_data) {
			$packet = new Packets\Packet($raw_data);
			if($packet::$is_xml) {
				$this->handleXmlPacket($socket, $packet);
			} else {
				$this->handleWorldPacket($socket, $packet);
			}
		}
	}
	
	abstract protected function handleXmlPacket($socket, $packet);
	abstract protected function handleWorldPacket($socket, $packet);
}

?>