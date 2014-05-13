<?php

namespace Kitsune\ClubPenguin;

final class World extends ClubPenguin {

	protected $world_handlers = array(
		"s" => array(
			"j#js" => "handleJoinWorld"
		)
	);
	
	protected function handleJoinWorld($socket, $packet) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->id != $packet::$data[1]) {
			return $this->removePenguin($penguin);
		}
		
		$login_key = $packet::$data[2];
		$db_login_key = $penguin->database->getColumnById($penguin->id, "LoginKey");
		
		if($db_login_key != $login_key) {
			$penguin->send("%xt%e%-1%101%");
			$penguin->database->updateColumnByid($penguin->id, "LoginKey", ""); // Ya' blew it
			return $this->removePenguin($penguin);
		}
		
		$penguin->database->updateColumnByid($penguin->id, "LoginKey", "");
	}

	protected function handleLogin($socket, $packet) {
		$penguin = $this->penguins[$socket];
		$raw_player_string = $packet::$data['body']['login']['nick'];
		$player_hashes = $packet::$data['body']['login']['pword'];
		
		$player_array = explode('|', $raw_player_string);
		list($id, $swid, $username) = $player_array;
		
		// Maybe check if id exists too? :)
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
	
}

?>