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
	
	protected function handleDisconnect($socket) {
		unset($this->penguins[$socket]);
		echo "Player disconnected\n";
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
	
	protected function removePenguin($penguin) {
		$this->removeClient($penguin->socket);
		unset($this->players[$penguin->socket]);
	}
	
	abstract protected function handleXmlPacket($socket, $packet);
	abstract protected function handleWorldPacket($socket, $packet);
	
}

?>

 _   ___ _                        
| | / (_) |                       
| |/ / _| |_ ___ _   _ _ __   ___ 
|    \| | __/ __| | | | '_ \ / _ \
| |\  \ | |_\__ \ |_| | | | |  __/
\_| \_/_|\__|___/\__,_|_| |_|\___|
