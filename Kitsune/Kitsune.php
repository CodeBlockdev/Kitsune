<?php

namespace Kitsune;

use Kitsune\ClubPenguin\Penguin;
use Kitsune\ClubPenguin\Packets\Packet;

abstract class Kitsune extends Spirit {

	protected $penguins = array();
	
	protected function handleAccept($socket) {
		$new_penguin = new Penguin($socket);
		$this->penguins[$socket] = $new_penguin;
	}
	
	protected function handleDisconnect($socket) {
		unset($this->penguins[$socket]);
		echo "Player disconnected\n";
	}
	
	protected function handleReceive($socket, $data) {
		echo "$data\n";
		
		$chunkedArray = explode("\0", $data);
		array_pop($chunkedArray);
		
		foreach($chunkedArray as $rawData) {
			$packet = Packet::Parse($rawData);
			
			if(Packet::$IsXML) {
				$this->handleXmlPacket($socket);
			} else {
				$this->handleWorldPacket($socket);
			}
		}
	}
	
	protected function removePenguin($penguin) {
		$this->removeClient($penguin->socket);
		unset($this->players[$penguin->socket]);
	}
	
	abstract protected function handleXmlPacket($socket);
	abstract protected function handleWorldPacket($socket);
	
}

?>

 _   ___ _                        
| | / (_) |                       
| |/ / _| |_ ___ _   _ _ __   ___ 
|    \| | __/ __| | | | '_ \ / _ \
| |\  \ | |_\__ \ |_| | | | |  __/
\_| \_/_|\__|___/\__,_|_| |_|\___|
