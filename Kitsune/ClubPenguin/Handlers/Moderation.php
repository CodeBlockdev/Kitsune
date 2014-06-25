<?php

namespace Kitsune\ClubPenguin\Handlers;

use Kitsune\Logging\Logger;
use Kitsune\ClubPenguin\Packets\Packet;

trait Moderation {

	protected function handleKickPlayerById($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->moderator) {
			$playerId = Packet::$Data[2];
			
			if(is_numeric($playerId)) {
				$targetPlayer = $this->getPlayerById($playerId);
				if($targetPlayer !== null) {
					$this->kickPlayer($targetPlayer, $penguin->username);
				}
			}
		}
	}
	
	protected function handleMutePlayerById($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->moderator) {
			$playerId = Packet::$Data[2];
			
			if(is_numeric($playerId)) {
				$targetPlayer = $this->getPlayerById($playerId);
				if($targetPlayer !== null) {
					$this->mutePlayer($targetPlayer, $penguin->username);
				}
			}
		}
	}
	
	protected function handleInitBan($socket) {
		$penguin = $this->penguins[$socket];

		if($penguin->moderator) {
			$playerId = Packet::$Data[2];
			$phrase = Packet::$Data[3];

			if(is_numeric($playerId)) {
				$targetPlayer = $this->getPlayerById($playerId);
				if($targetPlayer !== null) {
					$penguin->send("%xt%initban%-1%{$playerId}%0%0%{$phrase}%{$targetPlayer->username}%");
				}
			}
		}
	}
	
	protected function handleModeratorBan($socket) {
		$penguin = $this->penguins[$socket];
		$player = Packet::$Data[2];
		$banType = Packet::$Data[3];
		$banReason = Packet::$Data[4];
		$banDuration = Packet::$Data[5];
		$penguinName = Packet::$Data[6];
		$banNotes = Packet::$Data[7];
		if($penguin->moderator) {

			if(is_numeric($player)) {
				$targetPlayer = $this->getPlayerById($player);
				if($targetPlayer !== null) {
					if($banDuration !== 0){
						$targetPlayer->database->updateColumnById($targetPlayer->id, "Banned", strtotime("+".$banDuration." hours"));
					}else{
						$targetPlayer->database->updateColumnById($targetPlayer->id, "Banned", "perm");
					}
					$targetPlayer->send("%xt%ban%-1%$banType%$banReason%$banDuration%$banNotes%");
					$this->removePenguin($targetPlayer);
					Logger::Info("{$penguin->username} has banned {$targetPlayer->username} for $banDuration hours");
				}
			}
		}
	}
	
	protected function handleModeratorMessage($socket) {
		$penguin = $this->penguins[$socket];
		$type = Packet::$Data[1];
		$stype = Packet::$Data[2];
		$player = Packet::$Data[3];
		if($penguin->moderator) {

			if(is_numeric($player)) {
				$targetPlayer = $this->getPlayerById($player);
				if($targetPlayer !== null) {
					$targetPlayer->send("%xt%moderatormessage%-1%$stype%");
				}
			}
		}
	}
	
}

?>