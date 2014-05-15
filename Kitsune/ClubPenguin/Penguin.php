<?php

namespace Kitsune\ClubPenguin;
use Kitsune;

class Penguin {

	public $id;
	public $username;
	public $swid;

	public $identified;
	public $random_key;
	
	public $color, $head, $face, $neck, $body, $hand, $feet, $photo, $flag;
	public $age;

	public $avatar;
	public $coins;
	public $inventory;
	
	public $x = 0;
	public $y = 0;
	public $frame;
	
	public $room;
	
	public $socket;
	public $database;
	
	public function __construct($socket) {
		$this->socket = $socket;
		$this->database = new Kitsune\Database();
	}
	
	public function loadPlayer() {
		$this->random_key = null;
		
		$clothing = array("Color", "Head", "Face", "Neck", "Body", "Hand", "Feet", "Photo", "Flag");
		$player = array("Avatar", "RegistrationDate", "Inventory", "Coins");
		$columns = array_merge($clothing, $player);
		$player_array = $this->database->getColumnsByName($this->username, $columns);
		
		list($this->color, $this->head, $this->face, $this->neck, $this->body, $this->hand, $this->feet, $this->photo, $this->flag) = array_values($player_array);
		$this->age = floor((strtotime("NOW") - $player_array["RegistrationDate"]) / 86400); 
		$this->avatar = $player_array["Avatar"];
		$this->coins = $player_array["Coins"];
		$this->inventory = explode('%', $player_array["Inventory"]);
	}
	
	public function getPlayerString() {
		$player = array(
			$this->id,
			$this->username,
			45,
			$this->color,
			$this->head,
			$this->face,
			$this->neck,
			$this->body,
			$this->hand,
			$this->feet,
			$this->flag,
			$this->photo,
			$this->x,
			$this->y,
			$this->frame,
			1,
			146,
			$this->avatar
		);
		
		return implode('|', $player);
	}
	
	public function send($data) {
		echo "Outgoing: $data\n";
		$data .= "\0";
		$bytes_written = socket_send($this->socket, $data, strlen($data), 0);
		
		return $bytes_written;
	}
	
}

?>