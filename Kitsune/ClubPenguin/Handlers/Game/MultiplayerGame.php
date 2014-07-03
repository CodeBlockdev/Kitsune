<?php

namespace Kitsune\ClubPenguin\Handlers\Game;

use Kitsune\ClubPenguin\Packets\Packet;

trait MultiplayerGame {
		
	public $rinkPuck = array(0, 0, 0, 0);

	protected function handleLeaveGame($socket) {
		$penguin = $this->penguins[$socket];
		
		$seatId = array_search($penguin, $penguin->room->penguins);
		
		$penguin->room->send("%xt%lz%{$penguin->waddleRoom}%$seatId%");
	}
	
	protected function handleGameMove($socket) {
		$penguin = $this->penguins[$socket];
		
		$this->rinkPuck = array_splice(Packet::$Data, 3);
		
		$puckData = implode('%', $this->rinkPuck);
		
		$penguin->send("%xt%zm%{$penguin->room->internalId}%{$penguin->id}%$puckData%");
	}
	
	protected function handleGetGame($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->room->externalId == 998) {
			$numPlayers = Packet::$Data[2];
			
			$penguin->send("%xt%gz%{$penguin->waddleRoom}%2%$numPlayers%");
		} else {
			$puckData = implode('%', $this->rinkPuck);
			
			$penguin->send("%xt%gz%{$penguin->room->internalId}%$puckData%");
		}
	}

	protected function handleSendMove($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->waddleRoom !== null) {
			if($penguin->room->externalId == 998) {
				$moveType = Packet::$Data[2];
				
				if($moveType == "deal") {
					$numCards = Packet::$Data[3]; // How many new cards do we give them?
					
					$jitsuCards = array();
					for($numCard = 0; $numCard != $numCards; $numCard++) {
						array_push($jitsuCards, implode("|", $this->ninjaCards[array_rand($this->ninjaCards)]));
					}
					
					$jitsuCards = array_reverse($jitsuCards, true);
					
					$playerSeat = array_search($penguin, $penguin->room->penguins);
					
					$dealCards = implode("%", array_map(
						function($cardString, $cardIndex) use ($playerSeat) {
							return sprintf("%d|%s", $cardIndex * ($playerSeat + 10), $cardString);
						}, $jitsuCards, array_keys($jitsuCards)
					));
									
					$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%deal%$playerSeat%$dealCards%");
				}					
			} else {
				array_shift(Packet::$Data);
				
				$penguin->room->send("%xt%zm%" . implode('%', Packet::$Data) . '%');
			}
		}
	}
	
}

?>