<?php

namespace Kitsune\ClubPenguin;

use Kitsune\ClubPenguin\Packets\Packet;

final class Login extends ClubPenguin {

	public function __construct() {
		echo "Login server is online\n";
	}

	protected function handleLogin($socket) {
		$penguin = $this->penguins[$socket];
		$username = Packet::$Data['body']['login']['nick'];
		$password = Packet::$Data['body']['login']['pword'];
		
		echo "$username is attempting to login\n";
		
		if($penguin->database->usernameExists($username) === false) {
			$penguin->send("%xt%e%-1%101%");
			return $this->removePenguin($penguin);
		}
		
		$penguin_data = $penguin->database->getColumnsByName($username, array("ID", "Username", "Password", "SWID", "Email"));
		$encrypted_password = Hashing::getLoginHash($penguin_data["Password"], $penguin->random_key);
		
		if($encrypted_password != $password) {
			$penguin->send("%xt%e%-1%101%");
			return $this->removePenguin($penguin);
		} else {
			echo "Login is successful!\n";
			
			$confirmation_hash = md5($penguin->random_key);
			$friends_key = md5($penguin_data["ID"]); // May need to change this later!
			$login_time = time();
			
			$penguin->database->updateColumnById($penguin_data["ID"], "ConfirmationHash", $confirmation_hash);
			$penguin->database->updateColumnById($penguin_data["ID"], "LoginKey", $encrypted_password);
			
			$penguin->send("%xt%l%-1%{$penguin_data["ID"]}|{$penguin_data["SWID"]}|{$penguin_data["Username"]}|$encrypted_password|1|45|2|false|true|$login_time%$confirmation_hash%$friends_key%101,1%{$penguin_data["Email"]}%");
		}
	}
	
}

?>