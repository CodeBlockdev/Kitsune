<?php

namespace Kitsune\ClubPenguin\Handlers\Game;

use Kitsune\ClubPenguin\Room;
use Kitsune\ClubPenguin\Packets\Packet;

trait Waddle {

	public $waddlesById = array(
		103 => array('', ''), 102 => array('', ''), 101 => array('', '', ''), 100 => array('', '', '', ''), // Sled racing
		201 => array('', ''), 200 => array('', ''), 203 => array('', ''), 202 => array('', ''), // Card Jitsu
	);
	
	public $waddleUsers = array();
	
	private $waddleRoomId = null;
	
	public $waddleRooms = array();
	
	protected function getWaddlesPopulationById(array $waddleIds) {
		$waddlesPopulation = implode('%', array_map(
			function($waddleId) {
				return sprintf("%d|%s", $waddleId, implode(',', $this->waddlesById[$waddleId]));
			}, $waddleIds
		));
		
		return $waddlesPopulation;
	}
	
	protected function handleGetWaddlesPopulationById($socket) {
		$penguin = $this->penguins[$socket];
		
		$waddleIds = array_splice(Packet::$Data, 2);
		
		$waddlePopulation = $this->getWaddlesPopulationById($waddleIds);
		
		$penguin->send("%xt%gw%{$penguin->room->internalId}%$waddlePopulation%");
	}
	
	protected function handleGetWaddleCardJitsu($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->send("%xt%gwcj%{$penguin->room->internalId}%");
		
		$waddlesPopulation = $this->getWaddlesPopulationById(array(200, 201, 202, 203));
		
		$penguin->send("%xt%gw%{$penguin->room->internalId}%$waddlesPopulation%");
	}
	
	protected function handleSendJoinWaddleById($socket) {
		$penguin = $this->penguins[$socket];
		
		$this->leaveWaddle($penguin);
		
		$waddleId = Packet::$Data[2];
		$playerSeat = isset($this->waddleUsers[$waddleId]) ? sizeof($this->waddleUsers[$waddleId]) : 0;
		
		$this->waddleUsers[$waddleId][$playerSeat] = $penguin;
		$this->waddlesById[$waddleId][$playerSeat] = $penguin->username;
		
		$penguin->send("%xt%jw%{$penguin->room->internalId}%$playerSeat%");
		
		if($playerSeat === sizeof($this->waddlesById[$waddleId]) - 1) {
			$this->startWaddle($waddleId);
		}
		
		$penguin->room->send("%xt%uw%-1%$waddleId%$playerSeat%{$penguin->username}%{$penguin->id}%");
	}
	
	// %xt%cjms%19%998%200%1675%2%8%13%10%0%112136297%240661743%
	// %xt%cjms%19 (internal room) %998(game room id)%200 (waddle id id)%1675 (unique room id) %2 (how many players) %8 (my color) % 13 (their color) %10 (my belt) %0 (their belt)%112136297 (my id?)%240661743 (their id)%
	private function startWaddle($waddleId) {			
		foreach($this->waddlesById[$waddleId] as $seatIndex => $playerSeat) {
			$this->waddlesById[$waddleId][$seatIndex] = '';
		}
		
		if($this->waddleRoomId === null) {
			$this->waddleRoomId = strlen("Kitsune");
		}
		
		$this->waddleRoomId++;
		
		$roomId = $this->determineRoomId($waddleId);
		$internalId = $this->rooms[$roomId]->internalId;
		
		$waddleRoomId = ($this->waddleRoomId * 42) % 365;
		
		$this->waddleRooms[$waddleRoomId] = new Room($roomId, $internalId, false);
		
		$userCount = sizeof($this->waddleUsers[$waddleId]);
		
		foreach($this->waddleUsers[$waddleId] as $waddlePenguin) {
			$waddlePenguin->waddleRoom = $waddleRoomId;
			
			$waddlePenguin->send("%xt%sw%{$waddlePenguin->room->internalId}%$roomId%$waddleRoomId%$userCount%");
			
			if($roomId == 998) { // Card Jitsu training mat match
				if(!isset($jitsuTraining)) {
					$jitsuTraining = array(array(), array(), array());
				}
				
				$jitsuTraining[0][] = $waddlePenguin->color;
				$jitsuTraining[1][] = 0; // Belt colors!
				$jitsuTraining[2][] = $waddlePenguin->id;
			}
		}
		
		if($roomId == 998) {
			$jitsuMatch = "$roomId%$waddleId%$waddleRoomId%$userCount%";
			$jitsuMatch .= implode("%", $jitsuTraining[0]); // Colors
			$jitsuMatch .= "%" . implode("%", $jitsuTraining[1]); // Belts
			$jitsuMatch .= "%" . implode("%", $jitsuTraining[2]); // IDs
			
			foreach($this->waddleUsers[$waddleId] as $waddlePenguin) {
				$waddlePenguin->send("%xt%cjms%{$waddlePenguin->room->internalId}%$jitsuMatch%");
			}
		}
		
		$this->waddleUsers[$waddleId] = array();
	}
	
	private function determineRoomId($waddleId) {
		switch($waddleId) {
			case 100:
			case 101:
			case 102:
			case 103:
				return 999;
			case 200:
			case 201:
			case 202:
			case 203:
				return 998;
		}
	}
	
	protected function handleLeaveWaddle($socket) {
		$penguin = $this->penguins[$socket];
		
		$this->leaveWaddle($penguin);
	}
	
	public function leaveWaddle($penguin) {		
		foreach($this->waddleUsers as $waddleId => $leWaddle) {
			foreach($leWaddle as $playerSeat => $waddlePenguin) {
				if($waddlePenguin == $penguin) {
					$penguin->room->send("%xt%uw%-1%$waddleId%$playerSeat%");
				
					$this->waddlesById[$waddleId][$playerSeat] = '';
					unset($this->waddleUsers[$waddleId][$playerSeat]);
					
					if($penguin->waddleRoom !== null) {
						$penguin->room->remove($penguin);
						
						if(empty($this->waddleRooms[$penguin->waddleRoom]->penguins)) {
							unset($this->waddleRooms[$penguin->waddleRoom]);
						}
						
						$penguin->waddleRoom = null;
					}
					
					break;
				}
			}
		}
	}
	
	protected function handleJoinWaddle($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->room->remove($penguin);
		
		$roomId = Packet::$Data[2];
		
		if($penguin->waddleRoom !== null) {
			$this->waddleRooms[$penguin->waddleRoom]->add($penguin);
		}
	}

	protected function handleStartGame($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->room->externalId == 998) {
			foreach($penguin->room->penguins as $seatId => $ninjaPenguin) {
				if($ninjaPenguin->id != $penguin->id) {
					$penguin->send("%xt%jz%{$penguin->waddleRoom}%$seatId%{$ninjaPenguin->username}%{$ninjaPenguin->color}%0%");
					
					break;
				}
			}
			
			$gameString = implode("%", array_map(
				function($ninjaPenguin, $seatId) {
					return sprintf("%d|%s|%d|%d", $seatId, $ninjaPenguin->username, $ninjaPenguin->color, 0); // 0 is meant to be their belt id
				}, $penguin->room->penguins, array_keys($penguin->room->penguins)
			));
			
			$penguin->send("%xt%uz%{$penguin->waddleRoom}%$gameString%");
			$penguin->send("%xt%sz%{$penguin->waddleRoom}%");
		} elseif($penguin->room->externalId == 999) {
			$waddlePlayers = array();
			foreach($penguin->room->penguins as $waddlePenguin) {
				array_push($waddlePlayers, sprintf("%s|%d|%d|%s", $waddlePenguin->username, $waddlePenguin->color, $waddlePenguin->hand, $waddlePenguin->username));
			}
			
			$penguin->send("%xt%uz%-1%" . sizeof($waddlePlayers) . '%' . implode('%', $waddlePlayers) . '%');
		}
	}
	
}

?>
