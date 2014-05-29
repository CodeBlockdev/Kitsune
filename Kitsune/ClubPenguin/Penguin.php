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
	
	public $active_igloo;
	
	public $furniture = array();
	public $locations = array();
	public $floors = array();
	public $igloos = array();
	
	public $x = 0;
	public $y = 0;
	public $frame;
	
	public $room;
	
	public $walking_puffle;
	
	public $socket;
	public $database;
	
	public function __construct($socket) {
		$this->socket = $socket;
		$this->database = new Kitsune\Database();
	}
	
	public function walkPuffle($puffle_id, $walk_boolean) {
		$puffle = $this->database->getPuffleColumns($puffle_id, array("Type", "Subtype", "Hat"));
		$this->room->send("%xt%pw%{$this->room->internal_id}%{$this->id}%$puffle_id%{$puffle["Type"]}%{$puffle["Hat"]}%$walk_boolean%{$puffle["Subtype"]}%");
	}
	
	public function buyIgloo($igloo_id, $cost = 0) {
		$this->igloos[$igloo_id] = time();
		
		$igloos_string = implode(',', array_map(
			function($igloo, $purchase_date) {
				return $igloo . '|' . $purchase_date;
			}, array_keys($this->igloos), $this->igloos));
		
		$this->database->updateColumnById($this->id, "Igloos", $igloos_string);
		
		if($cost !== 0) {
			$this->coins -= $cost;
			$this->database->updateColumnById($this->id, "Coins", $this->coins);
		}
		
		$this->send("%xt%au%{$this->room->internal_id}%$igloo_id%{$this->coins}%");
	}
	
	public function buyFloor($floor_id, $cost = 0) {
		$this->floors[$floor_id] = time();
		
		$flooring_string = implode(',', array_map(
			function($floor, $purchase_date) {
				return $floor . '|' . $purchase_date;
			}, array_keys($this->floors), $this->floors));
		
		$this->database->updateColumnById($this->id, "Floors", $flooring_string);
		
		if($cost !== 0) {
			$this->coins -= $cost;
			$this->database->updateColumnById($this->id, "Coins", $this->coins);
		}
		
		$this->send("%xt%ag%{$this->room->internal_id}%$floor_id%{$this->coins}%");
	}
	
	public function buyFurniture($furniture_id, $cost = 0) {
		$furniture_quantity = 1;
		
		if(isset($this->furniture[$furniture_id])) {
			list($furniture_quantity) = $this->furniture[$furniture_id];
		}
		
		$this->furniture[$furniture_id] = array($furniture_quantity, time());
		
		$furniture_string = implode(',', array_map(
			function($furniture_id, $furniture_details) {
				list($quantity, $purchase_date) = $furniture_details;
				return $furniture_id . '|' . $purchase_date . '|' . $quantity;
			}, array_keys($this->furniture), $this->furniture));
		
		$this->database->updateColumnById($this->id, "Furniture", $furniture_string);
		
		if($cost !== 0) {
			$this->coins -= $cost;
			$this->database->updateColumnById($this->id, "Coins", $this->coins);
		}
		
		$this->send("%xt%af%{$this->room->internal_id}%$furniture_id%{$this->coins}%");
	}
	
	public function buyLocation($location_id, $cost = 0) {
		$this->locations[$location_id] = time();
		
		$locations_string = implode(',', array_map(
			function($location, $purchase_date) {
				return $location . '|' . $purchase_date;
			}, array_keys($this->locations), $this->locations));
		
		$this->database->updateColumnById($this->id, "Locations", $locations_string);
		
		if($cost !== 0) {
			$this->coins -= $cost;
			$this->database->updateColumnById($this->id, "Coins", $this->coins);
		}
		
		$this->send("%xt%aloc%{$this->room->internal_id}%$location_id%{$this->coins}%");
	}
	
	public function updateColor($item_id) {
		$this->color = $item_id;
		$this->database->updateColumnById($this->id, "Color", $item_id);
		$this->room->send("%xt%upc%{$this->room->internal_id}%{$this->id}%$item_id%");
	}
	
	public function updateHead($item_id) {
		$this->head = $item_id;
		$this->database->updateColumnById($this->id, "Head", $item_id);
		$this->room->send("%xt%uph%{$this->room->internal_id}%{$this->id}%$item_id%");
	}
	
	public function updateFace($item_id) {
		$this->face = $item_id;
		$this->database->updateColumnById($this->id, "Face", $item_id);
		$this->room->send("%xt%upf%{$this->room->internal_id}%{$this->id}%$item_id%");
	}
	
	public function updateNeck($item_id) {
		$this->neck = $item_id;
		$this->database->updateColumnById($this->id, "Neck", $item_id);
		$this->room->send("%xt%upn%{$this->room->internal_id}%{$this->id}%$item_id%");
	}
	
	public function updateBody($item_id) {
		$this->body = $item_id;
		$this->database->updateColumnById($this->id, "Body", $item_id);
		$this->room->send("%xt%upb%{$this->room->internal_id}%{$this->id}%$item_id%");
	}
	
	public function updateHand($item_id) {
		$this->hand = $item_id;
		$this->database->updateColumnById($this->id, "Hand", $item_id);
		$this->room->send("%xt%upa%{$this->room->internal_id}%{$this->id}%$item_id%");
	}
	
	public function updateFeet($item_id) {
		$this->feet = $item_id;
		$this->database->updateColumnById($this->id, "Feet", $item_id);
		$this->room->send("%xt%upe%{$this->room->internal_id}%{$this->id}%$item_id%");
	}
	
	public function updatePhoto($item_id) {
		$this->photo = $item_id;
		$this->database->updateColumnById($this->id, "Photo", $item_id);
		$this->room->send("%xt%upp%{$this->room->internal_id}%{$this->id}%$item_id%");
	}
	
	public function updateFlag($item_id) {
		$this->flag = $item_id;
		$this->database->updateColumnById($this->id, "Flag", $item_id);
		$this->room->send("%xt%upl%{$this->room->internal_id}%{$this->id}%$item_id%");
	}
	
	public function addItem($item_id, $cost) {
		array_push($this->inventory, $item_id);
		$this->database->updateColumnById($this->id, "Inventory", implode('%', $this->inventory));
		
		if($cost !== 0) {
			$this->coins -= $cost;
			$this->database->updateColumnById($this->id, "Coins", $this->coins);
		}
		
		$this->send("%xt%ai%{$this->room->internal_id}%$item_id%{$this->coins}%");
	}
	
	public function loadPlayer() {
		$this->random_key = null;
		
		$clothing = array("Color", "Head", "Face", "Neck", "Body", "Hand", "Feet", "Photo", "Flag", "Walking");
		$player = array("Avatar", "RegistrationDate", "Inventory", "Coins");
		$columns = array_merge($clothing, $player);
		$player_array = $this->database->getColumnsByName($this->username, $columns);
		
		list($this->color, $this->head, $this->face, $this->neck, $this->body, $this->hand, $this->feet, $this->photo, $this->flag) = array_values($player_array);
		$this->age = floor((strtotime("NOW") - $player_array["RegistrationDate"]) / 86400); 
		$this->avatar = $player_array["Avatar"];
		$this->coins = $player_array["Coins"];
		$this->inventory = explode('%', $player_array["Inventory"]);
		$this->walking_puffle = $player_array["Walking"];
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