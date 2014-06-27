<?php

namespace Kitsune\ClubPenguin\Handlers\Play;

use Kitsune\ClubPenguin\Packets\Packet;

trait Player {

	protected function handleGetLastRevision($socket) {
		$this->penguins[$socket]->send("%xt%glr%-1%10915%");
	}
	
	protected function handleGetPlayerInfoById($socket) {
		$penguin = $this->penguins[$socket];
		$penguinId = Packet::$Data[2];
		
		if($penguin->database->playerIdExists($penguinId)) {
			$playerArray = $penguin->database->getColumnsById($penguinId, array("Username", "SWID"));
			$penguin->send("%xt%pbi%{$penguin->room->internalId}%{$playerArray["SWID"]}%$penguinId%{$playerArray["Username"]}%");
		}	
	}
	
	protected function handleSendPlayerMove($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->x = Packet::$Data[2];
		$penguin->y = Packet::$Data[3];
		$penguin->room->send("%xt%sp%{$penguin->room->internalId}%{$penguin->id}%{$penguin->x}%{$penguin->y}%"); 
	}
	
	protected function handleSendPlayerFrame($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->frame = Packet::$Data[2];
		$penguin->room->send("%xt%sf%{$penguin->room->internalId}%{$penguin->id}%{$penguin->frame}%");
	}
	
	protected function handleSendHeartbeat($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->send("%xt%h%{$penguin->room->internalId}%");
	}
	
	protected function handleUpdatePlayerAction($socket) {
		$penguin = $this->penguins[$socket];
		$actionId = Packet::$Data[2];
		
		$penguin->room->send("%xt%sa%{$penguin->room->internalId}%{$penguin->id}%{$actionId}%");
	}
	
	protected function handleGetABTestData($socket) {
		
	}
	
	protected function handleSendEmote($socket) {
		$penguin = $this->penguins[$socket];
		$emoteId = Packet::$Data[2];
		
		$penguin->room->send("%xt%se%{$penguin->room->internalId}%{$penguin->id}%$emoteId%");
	}
	
	protected function handlePlayerThrowBall($socket) {
		$penguin = $this->penguins[$socket];
		
		$x = Packet::$Data[2];
		$y = Packet::$Data[3];
		
		$penguin->room->send("%xt%sb%{$penguin->room->internalId}%{$penguin->id}%$x%$y%");
	}
	
	protected function handleGetBestFriendsList($socket) {
		$penguin = $this->penguins[$socket];
		$penguin->send("%xt%gbffl%{$penguin->room->internalId}%");
	}
	
	protected function handlePlayerBySwidUsername($socket) {
		$penguin = $this->penguins[$socket];
		$swidList = Packet::$Data[2];
		
		$usernameList = $penguin->database->getUsernamesBySwid($swidList);
		$penguin->send("%xt%pbsu%{$penguin->room->internalId}%$usernameList%");
	}
	
	protected function handleSafeMessage($socket) {
		$penguin = $this->penguins[$socket];
		$messageId = Packet::$Data[2];
		
		if(is_numeric($messageId)) {
			$penguin->room->send("%xt%ss%{$penguin->room->internalId}%{$penguin->id}%$messageId%");
		}
	}
	
}

?>