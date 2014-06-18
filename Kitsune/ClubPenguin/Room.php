<?php

namespace Kitsune\ClubPenguin;

class Room {

	public $penguins = array();
	
	public $externalId;
	public $internalId;
	
	public function __construct($externalId, $internalId) {
		$this->externalId = $externalId;
		$this->internalId = $internalId;
	}
	
	public function add($penguin) {
		array_push($this->penguins, $penguin);
		
		$room_string = $this->getRoomString();
		$penguin->send("%xt%jr%{$this->internalId}%{$this->externalId}%$room_string%");
		$this->send("%xt%ap%{$this->internalId}%{$penguin->getPlayerString()}%");
		$penguin->room = $this;
	}
	
	public function remove($penguin) {
		$player_index = array_search($penguin, $this->penguins);
		unset($this->penguins[$player_index]);
		$this->send("%xt%rp%{$this->internalId}%{$penguin->id}%");
	}
	
	public function send($data) {
		foreach($this->penguins as $penguin) {
			$penguin->send($data);
		}
	}
	
	private function getRoomString() {
		$room_string = implode('%', array_map(function($penguin) {
			return $penguin->getPlayerString();
		}, $this->penguins));
		
		return $room_string;
	}
	
}

?>