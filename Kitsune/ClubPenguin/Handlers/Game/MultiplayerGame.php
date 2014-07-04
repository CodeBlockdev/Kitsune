<?php

namespace Kitsune\ClubPenguin\Handlers\Game;

use Kitsune\Logging\Logger;
use Kitsune\ClubPenguin\Packets\Packet;

trait MultiplayerGame {
		
	public $rinkPuck = array(0, 0, 0, 0);
	
	// This array keeps track of the players' deck
	// This array also keeps track of the last drawn cards for each round
	// $jitsuMatch[$uniqueWaddleId][$playerSeat][$cardIndex] = $cardId
	// $jitsuMatch[$uniqueWaddleId]["rounds"][$round] = array(0 => array("f"), 1 => array("w"));
	public $jitsuMatch = array(); 

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
					$playerSeat = array_search($penguin, $penguin->room->penguins);
					
					$jitsuCards = array();
					for($numCard = 0; $numCard != $numCards; $numCard++) {
						$cardId = array_rand($this->ninjaCards);
						
						$cardDetails = sprintf("%d|%s", $cardId, implode("|", $this->ninjaCards[$cardId]));
						
						array_push($jitsuCards, $cardDetails);
					}
					
					$jitsuCards = array_reverse($jitsuCards, true);
					
					//Logger::Info("$playerSeat ({$penguin->username})'s deck: ");
					//var_dump($jitsuCards);
					
					$dealCards = implode("%", array_map(
						function($cardString, $cardIndex) use ($playerSeat, $penguin) {
							$cardIndex = ++$cardIndex * ($playerSeat + 10);
							list($cardId) = explode("|", $cardString);
							
							$this->jitsuMatch[$penguin->waddleRoom][$playerSeat][$cardIndex] = $cardId;
							
							return sprintf("%d|%s", $cardIndex, $cardString);
						}, $jitsuCards, array_keys($jitsuCards)
					));
									
					$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%deal%$playerSeat%$dealCards%");
				} elseif($moveType == "pick") {
					$cardIndex = Packet::$Data[3]; // Choosen card
					$playerSeat = array_search($penguin, $penguin->room->penguins);

					$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%pick%$playerSeat%$cardIndex%");
					
					//var_dump($this->jitsuMatch[$penguin->waddleRoom]);
					//Logger::Debug("Room->penguins");
					//var_dump(array_keys($penguin->room->penguins));
					
					//Logger::Info("Player $playerSeat ({$penguin->username}) chose $cardIndex!");
					
					$cardId = $this->jitsuMatch[$penguin->waddleRoom][$playerSeat][$cardIndex];
					$cardElement = $this->ninjaCards[$cardId][0];
					$cardValue = $this->ninjaCards[$cardId][1];
					$powerId = $this->ninjaCards[$cardId][3];
					
					if($powerId != 0) {
						$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%power%$playerSeat%$cardIndex%$powerId%");
					}
					
					if(empty($this->jitsuMatch[$penguin->waddleRoom]["rounds"])) {
						$this->jitsuMatch[$penguin->waddleRoom]["rounds"][0][$playerSeat] = array($cardElement, $cardValue);
						$this->jitsuMatch[$penguin->waddleRoom]["score"] = array();
					}

					$roundId = key($this->jitsuMatch[$penguin->waddleRoom]["rounds"]);
					//Logger::Debug("Judging round $roundId");
					if(!isset($this->jitsuMatch[$penguin->waddleRoom]["rounds"][$roundId][$playerSeat])) {
						$this->jitsuMatch[$penguin->waddleRoom]["rounds"][$roundId][$playerSeat] = array($cardElement, $cardValue);
					}
					
					if(sizeof($this->jitsuMatch[$penguin->waddleRoom]["rounds"][$roundId]) == 2) { // Both players have drawn cards
						$opponentSeat = $playerSeat == 0 ? 1 : 0;
						list($opponentElement, $opponentValue) = $this->jitsuMatch[$penguin->waddleRoom]["rounds"][$roundId][$opponentSeat];
						
						// Maybe use switch and create a function for this stuff
						if($cardElement == "f") {
							if($opponentElement == "w") {
								$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%judge%$opponentSeat%-1%");
								$this->jitsuMatch[$penguin->waddleRoom]["score"][$opponentSeat]  += $opponentValue;
							} elseif($opponentElement == "i") {
								$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%judge%$playerSeat%-1%");
								$this->jitsuMatch[$penguin->waddleRoom]["score"][$playerSeat] += $cardValue;
							} elseif($opponentElement == "f") {
								if($cardValue > $opponentValue) {
									$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%judge%$playerSeat%-1%");
									$this->jitsuMatch[$penguin->waddleRoom]["score"][$playerSeat] += $cardValue;
								} elseif($opponentValue > $cardValue) {
									$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%judge%$opponentSeat%-1%");
									$this->jitsuMatch[$penguin->waddleRoom]["score"][$opponentSeat] += $opponentValue;
								} else {
									Logger::Warn("A draw! This is here to make sure it works!");
								}
							}
						} elseif($cardElement == "w") {
							if($opponentElement == "i") {
								$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%judge%$opponentSeat%-1%");
								$this->jitsuMatch[$penguin->waddleRoom]["score"][$opponentSeat]  += $opponentValue;
							} elseif($opponentElement == "f") {
								$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%judge%$playerSeat%-1%");
								$this->jitsuMatch[$penguin->waddleRoom]["score"][$playerSeat] += $cardValue;
							} elseif($opponentElement == "w") {
								if($cardValue > $opponentValue) {
									$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%judge%$playerSeat%-1%");
									$this->jitsuMatch[$penguin->waddleRoom]["score"][$playerSeat] += $cardValue;
								} elseif($opponentValue > $cardValue) {
									$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%judge%$opponentSeat%-1%");
									$this->jitsuMatch[$penguin->waddleRoom]["score"][$opponentSeat] += $opponentValue;
								} else {
									Logger::Warn("A draw! This is here to make sure it works!");
								}
							}
						} elseif($cardElement == "i") {
							if($opponentElement == "w") {
								$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%judge%$playerSeat%-1%");
								$this->jitsuMatch[$penguin->waddleRoom]["score"][$playerSeat]  += $playerSeat;
							} elseif($opponentElement == "f") {
								$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%judge%$opponentSeat%-1%");
								$this->jitsuMatch[$penguin->waddleRoom]["score"][$opponentSeat] += $cardValue;
							} elseif($opponentElement == "i") {
								if($cardValue > $opponentValue) {
									$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%judge%$playerSeat%-1%");
									$this->jitsuMatch[$penguin->waddleRoom]["score"][$playerSeat] += $cardValue;
								} elseif($opponentValue > $cardValue) {
									$penguin->room->send("%xt%zm%{$penguin->waddleRoom}%judge%$opponentSeat%-1%");
									$this->jitsuMatch[$penguin->waddleRoom]["score"][$opponentSeat] += $opponentValue;
								} else {
									Logger::Warn("A draw! This is here to make sure it works!");
								}
							}
						}
						
						$newRound = ++$roundId;
						$this->jitsuMatch[$penguin->waddleRoom]["rounds"][$newRound] = array();
						Logger::Info("New round! $newRound");
					}
				}
			} else {
				array_shift(Packet::$Data);
				
				$penguin->room->send("%xt%zm%" . implode('%', Packet::$Data) . '%');
			}
		}
	}
	
}

?>