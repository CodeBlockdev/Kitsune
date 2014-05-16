<?php

namespace Kitsune\ClubPenguin;

class Room {

	public $penguins = array();
	
	public $external_id;
	public $internal_id;
	
	public function __construct($external_id, $internal_id) {
		$this->external_id = $external_id;
		$this->internal_id = $internal_id;
	}
	
	public function add($penguin) {
		array_push($this->penguins, $penguin);
		
		$room_string = $this->getRoomString();
		$penguin->send("%xt%jr%{$this->internal_id}%{$this->external_id}%$room_string%");
		$this->send("%xt%ap%{$this->internal_id}%{$penguin->getPlayerString()}%");
		$penguin->room = $this;
	}
	
	public function remove($penguin) {
		$player_index = array_search($penguin, $this->penguins);
		unset($this->penguins[$player_index]);
		$this->send("%xt%rp%{$this->internal_id}%{$penguin->id}%");
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