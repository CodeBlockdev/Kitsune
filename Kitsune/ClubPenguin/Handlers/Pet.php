<?php

namespace Kitsune\ClubPenguin\Handlers;

use Kitsune\Logging\Logger;
use Kitsune\ClubPenguin\Packets\Packet;

trait Pet {

	protected function handleGetPufflesByPlayerId($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[2];
		$roomType = Packet::$Data[3];
		
		if($penguin->database->playerIdExists($playerId)) {
			$puffleData = $penguin->database->getPuffles($playerId, $roomType);
			$ownedPuffles = sizeof($puffleData);
			
			$walkingPuffle = null;
			if(!empty($penguin->walkingPuffle)) {
				list($walkingPuffle) = $penguin->walkingPuffle;
			}
			
			$playerPuffles = $this->joinPuffleData($puffleData, $walkingPuffle, true);
			
			$penguin->send("%xt%pg%{$penguin->room->internalId}%$ownedPuffles%$playerPuffles%");
		}
	}
	
	protected function handleCheckPuffleNameWithResponse($socket) {
		$penguin = $this->penguins[$socket];
		$puffleName = Packet::$Data[2];
		
		$nameOkay = intval(ctype_alpha($puffleName));
		$penguin->send("%xt%checkpufflename%{$penguin->room->internalId}%$puffleName%$nameOkay%");
	}
	
	// Check if they exceed puffle limit (?)
	// Also check if types are valid!
	protected function handleAdoptPuffle($socket) {
		$penguin = $this->penguins[$socket];
		$puffleType = Packet::$Data[2];
		$puffleName = ucfirst(Packet::$Data[3]);
		$puffleSubtype = Packet::$Data[4];
		
		if($puffleSubtype == 0) {
			$puffleCost = 400;
		} else {
			$puffleCost = 800;
		}
		
		if(is_numeric($puffleType) && is_numeric($puffleSubtype)) {
			$puffleId = $penguin->database->adoptPuffle($penguin->id, $puffleName, $puffleType, $puffleSubtype);
			$adoptionDate = time();
			
			if($puffleSubtype == 0) {
				$puffleSubtype = "";
			}
			
			$penguin->buyPuffleCareItem(3, 0, 5, true); // Puffle O's
			$penguin->buyPuffleCareItem(76, 0, 1, true); // Apple
			
			$postcardId = $penguin->database->sendMail($penguin->id, "sys", 0, $puffleName, $adoptionDate, 111);
			$penguin->send("%xt%mr%-1%sys%0%111%$puffleName%$adoptionDate%$postcardId%"); 
			
			$penguin->setCoins($penguin->coins - $puffleCost);
			$penguin->send("%xt%pn%{$penguin->room->internalId}%{$penguin->coins}%$puffleId|$puffleType|$puffleSubtype|$puffleName|$adoptionDate|100|100|100|100|0|0|0|1|%");
			
			$penguin->database->updateColumnById($penguin->id, "Walking", $puffleId);
			$penguin->walkingPuffle = $penguin->database->getPuffleColumns($puffleId, array("Type", "Subtype", "Hat") );
			$penguin->walkingPuffle = array_values($penguin->walkingPuffle);
			array_unshift($penguin->walkingPuffle, $puffleId);
		}
	}
	
	protected function handleGetMyPuffleStats($socket) {
		$penguin = $this->penguins[$socket];
		
		$puffleStats = $penguin->database->getPuffleStats($penguin->id);
		
		$penguin->send("%xt%pgmps%{$penguin->room->internalId}%$puffleStats%");
	}
	
	protected function handleSendPuffleWalk($socket) {
		$penguin = $this->penguins[$socket];
		$puffleId = Packet::$Data[2];
		$walkBoolean = Packet::$Data[3];
		
		if(is_numeric($puffleId) && $penguin->database->ownsPuffle($puffleId, $penguin->id)) {
			if($walkBoolean == 0 || $walkBoolean == 1) {
				$penguin->walkPuffle($puffleId, $walkBoolean);
			}
			
			if($walkBoolean == 0) {
				$penguin->database->updateColumnById($penguin->id, "Walking", 0);
			} else {
				$penguin->database->updateColumnById($penguin->id, "Walking", $puffleId);
			}
		}
	}
	
	protected function handlePuffleSwap($socket) {
		$penguin = $this->penguins[$socket];
		$puffleId = Packet::$Data[2];
		
		if(is_numeric($puffleId) && $penguin->database->ownsPuffle($puffleId, $penguin->id)) {
			$puffle = $penguin->database->getPuffleColumns($puffleId, array("Type", "Subtype", "Hat"));
			$penguin->room->send("%xt%pufflewalkswap%{$penguin->room->internalId}%{$penguin->id}%$puffleId%{$puffle["Type"]}%{$puffle["Subtype"]}%1%{$puffle["Hat"]}%");
			$penguin->database->updateColumnById($penguin->id, "Walking", $puffleId);
			$penguin->walkingPuffle = $penguin->database->getPuffleColumns($puffleId, array("Type", "Subtype", "Hat") );
			$penguin->walkingPuffle = array_values($penguin->walkingPuffle);
			array_unshift($penguin->walkingPuffle, $puffleId);
		}
	}
	
	protected function handlePuffleTrick($socket) {
		$penguin = $this->penguins[$socket];
		$puffleTrick = Packet::$Data[2];
		
		if(is_numeric($puffleTrick)) {
			$penguin->room->send("%xt%puffletrick%{$penguin->room->internalId}%{$penguin->id}%$puffleTrick%");
		}
	}
	
	protected function handleSendChangePuffleRoom($socket) {
		$penguin = $this->penguins[$socket];
		$puffleId = Packet::$Data[2];
		$roomType = Packet::$Data[3];
		
		if($roomType == "igloo" || $roomType == "backyard") {
			if($penguin->database->ownsPuffle($puffleId, $penguin->id)) {
				$toBackyard = intval($roomType == "backyard");
				$penguin->database->sendChangePuffleRoom($puffleId, $toBackyard);
				$penguin->send("%xt%puffleswap%{$penguin->room->internalId}%$puffleId%$roomType%");				
			}
		}
	}
	
	protected function handleGetPuffleCareInventory($socket) {
		$penguin = $this->penguins[$socket];
		
		$careInventory = "";
		
		if(!empty($penguin->careInventory)) {
			$careInventory = implode('%', array_map(
				function($itemId, $quantity) {
					return sprintf("%d|%d", $itemId, $quantity);
				}, array_keys($penguin->careInventory), $penguin->careInventory
			));
		}
		
		$penguin->send("%xt%pgpi%{$penguin->room->internalId}%$careInventory%");
	}
	
	protected function handleSendBuyPuffleCareItem($socket) {
		$penguin = $this->penguins[$socket];
		
		$itemId = Packet::$Data[2];
		
		if(!isset($this->careItems[$itemId])) {
			$penguin->send("%xt%e%-1%402%");
		} else {
			list($itemCost, $itemQuantity) = $this->careItems[$itemId];
			
			if($penguin->coins < $itemCost) {
				$penguin->send("%xt%e%-1%401%");
			} else {
				$penguin->buyPuffleCareItem($itemId, $itemCost, $itemQuantity);
			}
		}
	}
	
	protected function handleGetPuffleHanderStatus($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->send("%xt%phg%{$penguin->room->internalId}%1%");
	}
	
	protected function handleVisitorHatUpdate($socket) {
		$penguin = $this->penguins[$socket];
		
		$puffleId = Packet::$Data[2];
		$hatId = Packet::$Data[3];

		if($penguin->database->ownsPuffle($puffleId, $penguin->id) && isset($this->careItems[$hatId])) {
			$penguin->database->updatePuffleColumn($puffleId, "Hat", $hatId);
			
			$penguin->room->send("%xt%puphi%{$penguin->room->internalId}%$puffleId%$hatId%");
		}
	}
	
	protected function handleSendPufflePlay($socket) {
		Logger::Warn("Need to log packets");
	}
	
	protected function handlePenguinOnSlideOrZipline($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->room->send("%xt%followpath%{$penguin->room->internalId}%{$penguin->id}%" .  Packet::$Data[2] ."%");
	}

}

?>