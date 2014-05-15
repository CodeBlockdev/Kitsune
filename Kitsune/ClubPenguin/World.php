<?php

namespace Kitsune\ClubPenguin;

final class World extends ClubPenguin {

	protected $world_handlers = array(
		"s" => array(
			"j#js" => "handleJoinWorld",
			"i#gi" => "handleGetInventoryList",
			"l#mst" => "handleMailStartEngine",
			"u#glr" => "handleGetLastRevision",
			"l#mg" => "handleGetMail",
			"u#gabcms" => "handleGetABTestData", // Currently has no method
			"j#jr" => "handleJoinRoom",
			"u#pbi" => "handleGetPlayerInfoById",
			"u#sp" => "handleSendPlayerMove",
			"u#sf" => "handleSendPlayerFrame",
			"u#h" => "handleSendHeartbeat"
		)
	);
	
	private $rooms = array();
	
	public function __construct() {
		echo "Setting up rooms.. ";
		$rooms_json = file_get_contents("http://media1.clubpenguin.com/play/en/web_service/game_configs/rooms.json");
		$rooms = json_decode($rooms_json, true);
		foreach($rooms as $room => $details) {
			$this->rooms[$room] = new Room($room, sizeof($this->rooms) + 1);
		}
		echo "done\n";
	}
	
	protected function handleSendHeartbeat($socket, $packet) {
		$penguin = $this->penguins[$socket];
		
		$penguin->send("%xt%h%{$penguin->room->internal_id}%");
	}
	
	protected function handleSendPlayerFrame($socket, $packet) {
		$penguin = $this->penguins[$socket];
		
		$penguin->frame = $packet::$data[1];
		$penguin->room->send("%xt%sf%{$penguin->room->internal_id}%{$penguin->id}%{$penguin->frame}%");
	}
		
	protected function handleSendPlayerMove($socket, $packet) {
		$penguin = $this->penguins[$socket];
		
		$penguin->x = $packet::$data[1];
		$penguin->y = $packet::$data[2];
		$penguin->room->send("%xt%sp%{$penguin->room->internal_id}%{$penguin->id}%{$penguin->x}%{$penguin->y}%"); 
	}
	
	protected function handleGetPlayerInfoById($socket, $packet) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->database->playerIdExists($packet::$data[1])) {
			$player_array = $penguin->database->getColumnsById($packet::$data[1], array("Username", "SWID"));
			$penguin->send("%xt%pbi%{$penguin->room->internal_id}%{$player_array["SWID"]}%{$packet::$data[1]}%{$player_array["Username"]}%");
		}	
	}
	
	protected function handleJoinRoom($socket, $packet) {
		$penguin = $this->penguins[$socket];
		
		$room = $packet::$data[1];
		$x = $packet::$data[2];
		$y = $packet::$data[3];
		
		$this->joinRoom($penguin, $room, $x, $y);
	}
	
	protected function handleGetMail($socket, $packet) {
		$this->penguins[$socket]->send("%xt%mg%-1%");
	}
	
	protected function handleGetLastRevision($socket, $packet) {
		$this->penguins[$socket]->send("%xt%glr%-1%10915%");
	}
	
	protected function handleMailStartEngine($socket, $packet) {
		$penguin = $this->penguins[$socket];
		
		$penguin->send("%xt%mst%-1%0%0%");
	}
	
	protected function handleGetInventoryList($socket, $packet) {
		$penguin = $this->penguins[$socket];
		
		$inventory_list = implode('%', $penguin->inventory);
		$penguin->send("%xt%gi%-1%$inventory_list%");
	}
	
	public function joinRoom($penguin, $room_id, $x = 0, $y = 0) {
		if(!isset($this->rooms[$room_id])) {
			return;
		} elseif(isset($penguin->room)) {
			$penguin->room->remove($penguin);
		}
		
		$penguin->frame = 1;
		$penguin->x = $x;
		$penguin->y = $y;
		$this->rooms[$room_id]->add($penguin);
	}
	
	protected function handleJoinWorld($socket, $packet) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->id != $packet::$data[1]) {
			return $this->removePenguin($penguin);
		}
		
		$login_key = $packet::$data[2];
		$db_login_key = $penguin->database->getColumnById($penguin->id, "LoginKey");
		
		if($db_login_key != $login_key) {
			$penguin->send("%xt%e%-1%101%");
			$penguin->database->updateColumnByid($penguin->id, "LoginKey", "");
			return $this->removePenguin($penguin);
		}
		
		$penguin->database->updateColumnByid($penguin->id, "LoginKey", "");
		
		$penguin->loadPlayer();
		$penguin->send("%xt%activefeatures%-1%");
		$penguin->send("%xt%js%-1%1%0%0%1%");
		$penguin->send("%xt%gps%-1%{$penguin->id}%");
		$penguin->send("%xt%pgu%-1%");
		
		$player_string = $penguin->getPlayerString();
		$current_time = time();
		
		$load_player = "$player_string|%{$penguin->coins}%0%1440%$current_time%{$penguin->age}%0%7521%%7%1%0%211843";
		$penguin->send("%xt%lp%-1%$load_player%");
		$this->joinRoom($penguin, 100, 0, 0);
		// The 0 after the player id is probably a transformation id, will be looking into a proper implementation
		$penguin->room->send("%xt%spts%-1%{$penguin->id}%0%{$penguin->avatar}%");
	}

	protected function handleLogin($socket, $packet) {
		$penguin = $this->penguins[$socket];
		$raw_player_string = $packet::$data['body']['login']['nick'];
		$player_hashes = $packet::$data['body']['login']['pword'];
		
		$player_array = explode('|', $raw_player_string);
		list($id, $swid, $username) = $player_array;
		
		if($penguin->database->playerIdExists($id) === false) {
			return $this->removePenguin($penguin);
		}
		
		if($penguin->database->usernameExists($username) === false) {
			$penguin->send("%xt%e%-1%101%");
			return $this->removePenguin($penguin);
		}
		
		$hashes_array = explode('#', $player_hashes);
		list($login_key, $confirmation_hash) = $hashes_array;
		
		$db_confirmation_hash = $penguin->database->getColumnById($id, "ConfirmationHash");
		if($db_confirmation_hash != $confirmation_hash) {
			$penguin->send("%xt%e%-1%101%");
			return $this->removePenguin($penguin);
		} else {
			echo "Login successful!\n";
			$penguin->database->updateColumnByid($id, "ConfirmationHash", ""); // Maybe the column should be cleared even when the login is unsuccessful
			$penguin->id = $id;
			$penguin->swid = $swid;
			$penguin->username = $username;
			$penguin->identified = true;
			$penguin->send("%xt%l%-1%");
		}
		
	}
	
	protected function handleDisconnect($socket) {
		$penguin = $this->penguins[$socket];
		$penguin->room->remove($penguin);
		unset($this->penguins[$socket]);
		
		echo "Player disconnected\n";
	}
	
}

?>