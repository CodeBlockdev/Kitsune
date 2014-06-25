<?php

namespace Kitsune\ClubPenguin\Handlers;

use Kitsune\ClubPenguin\Room;
use Kitsune\ClubPenguin\Packets\Packet;

trait Navigation {

	protected function handleJoinWorld($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->id != Packet::$Data[2]) {
			return $this->removePenguin($penguin);
		}
		
		$loginKey = Packet::$Data[3];
		$dbLoginKey = $penguin->database->getColumnById($penguin->id, "LoginKey");
		
		if($dbLoginKey != $loginKey) {
			$penguin->send("%xt%e%-1%101%");
			$penguin->database->updateColumnByid($penguin->id, "LoginKey", "");
			return $this->removePenguin($penguin);
		}
		
		$penguin->database->updateColumnByid($penguin->id, "LoginKey", "");
		
		$penguin->loadPlayer();
		
		$this->penguinsById[$penguin->id] = $penguin;
		
		$penguin->send("%xt%activefeatures%-1%");
		
		$isModerator = intval($penguin->moderator);
		$penguin->send("%xt%js%-1%1%0%$isModerator%1%");
		$stamps = rtrim(str_replace(",", "|", $penguin->database->getColumnById($penguin->id, "Stamps")), "|");
		$penguin->send("%xt%gps%-1%{$penguin->id}%$stamps%");
		
		$puffleData = $penguin->database->getPlayerPuffles($penguin->id);
		$puffles = $this->joinPuffleData($puffleData);
		
		$penguin->send("%xt%pgu%-1%$puffles%");
		
		$playerString = $penguin->getPlayerString();
		$loginTime = time(); // ?
		
		$loadPlayer = "$playerString|%{$penguin->coins}%0%1440%$loginTime%{$penguin->age}%0%7521%%7%1%0%211843";
		$penguin->send("%xt%lp%-1%$loadPlayer%");
		
		$openRoom = $this->getOpenRoom();
		$this->joinRoom($penguin, $openRoom, 0, 0);
				
		// The 0 after the player id is probably a transformation id, will be looking into a proper implementation
		$penguin->room->send("%xt%spts%-1%{$penguin->id}%0%{$penguin->avatar}%");
	}
	
	protected function handleJoinRoom($socket) {
		$penguin = $this->penguins[$socket];
		
		$room = Packet::$Data[2];
		$x = Packet::$Data[3];
		$y = Packet::$Data[3];
		
		$this->joinRoom($penguin, $room, $x, $y);
	}
	
	protected function handleJoinPlayerRoom($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[2];
		$roomType = Packet::$Data[3];
		
		if($penguin->database->playerIdExists($playerId)) {
			$externalId = $playerId + 1000;
			
			if(!isset($this->rooms[$externalId])) {
				$this->rooms[$externalId] = new Room($externalId, $playerId);
			}
			
			$penguin->send("%xt%jp%$playerId%$playerId%$externalId%$roomType%");
			$this->joinRoom($penguin, $externalId);
		}
	}
	
}

?>