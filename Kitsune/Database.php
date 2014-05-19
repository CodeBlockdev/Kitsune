<?php

namespace Kitsune;

class Database extends \PDO {

	private static $config_file = "Database.xml";
	
	public function __construct() {
		$config_object = simplexml_load_file(self::$config_file);
		$connection_string = sprintf("mysql:dbname=%s;host=%s", $config_object->name, $config_object->address);

		try {
			parent::__construct($connection_string, $config_object->username, $config_object->password);
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function iglooExists($igloo_id) {
		try {
			$igloo_exists_stmt = $this->prepare("SELECT ID FROM `igloos` WHERE ID = :Igloo");
			$igloo_exists_stmt->bindValue(":Igloo", $igloo_id);
			$igloo_exists_stmt->execute();
			$row_count = $igloo_exists_stmt->rowCount();
			$igloo_exists_stmt->closeCursor();
			
			return $row_count > 0;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function addIglooLayout($player_id) {
		try {
			$add_igloo_stmt = $this->prepare("INSERT INTO `igloos` (`ID`, `Owner`, `Type`, `Floor`, `Music`, `Furniture`, `Location`, `Likes`, `Locked`) VALUES (NULL, :Owner, '1', '0', '0', '', '1', '', '1');");
			$add_igloo_stmt->bindValue(":Owner", $player_id);
			$add_igloo_stmt->execute();
			$add_igloo_stmt->closeCursor();
			
			$igloo_id = $this->lastInsertId();
			
			$this->updateColumnById($player_id, "Igloo", $igloo_id);
			
			return $igloo_id;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function getOwnedIglooCount($player_id) {
		try {
			$igloo_count_stmt = $this->prepare("SELECT ID FROM `igloos` WHERE Owner = :Owner");
			$igloo_count_stmt->bindValue(":Owner", $player_id);
			$igloo_count_stmt->execute();
			
			$igloo_count = $igloo_count_stmt->rowCount();
			$igloo_count_stmt->closeCursor();
			
			return $igloo_count;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function updateIglooColumn($igloo_id, $column, $value) {
		try {
			$update_igloo_stmt = $this->prepare("UPDATE `igloos` SET $column = :Value WHERE ID = :Igloo");
			$update_igloo_stmt->bindValue(":Value", $value);
			$update_igloo_stmt->bindValue(":Igloo", $igloo_id);
			$update_igloo_stmt->execute();
			$update_igloo_stmt->closeCursor();
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function getAllIglooLayouts($player_id) {
		try {
			$igloos_stmt = $this->prepare("SELECT ID FROM `igloos` WHERE Owner = :Owner");
			$igloos_stmt->bindValue(":Owner", $player_id);
			$igloos_stmt->execute();
			
			$owned_igloos = $igloos_stmt->fetchAll(\PDO::FETCH_ASSOC);
			$igloos_stmt->closeCursor();
			
			$owned_igloos = array_column($owned_igloos, "ID");
			
			$slot_number = 0;
			$igloo_layouts = array();
			
			foreach($owned_igloos as $owned_igloo) {
				array_push($igloo_layouts, $this->getIglooDetails($owned_igloo, ++$slot_number));
			}
			
			$igloo_layouts = implode('%', $igloo_layouts);
			
			return $igloo_layouts;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function getIglooDetails($igloo_id, $slot_number = 1) {
		try {
			$igloo_stmt = $this->prepare("SELECT Type, Floor, Music, Location, Likes, Locked, Furniture FROM `igloos` WHERE ID = :Igloo");
			$igloo_stmt->bindValue(":Igloo", $igloo_id);
			$igloo_stmt->execute();
			$igloo_array = $igloo_stmt->fetch(\PDO::FETCH_ASSOC);
			$igloo_stmt->closeCursor();
			
			$igloo_details = $igloo_id;
			$igloo_details .= ':' . $slot_number;
			$igloo_details .= ':0';
			$igloo_details .= ':' . $igloo_array["Locked"];
			$igloo_details .= ':' . $igloo_array["Music"];
			$igloo_details .= ':' . $igloo_array["Floor"];
			$igloo_details .= ':' . $igloo_array["Location"];
			$igloo_details .= ':' . $igloo_array["Type"];
			$igloo_details .= ':' . 0; // Igloo likes!
			$igloo_details .= ':' . $igloo_array["Furniture"];
			
			return $igloo_details;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function playerIdExists($id) {
		try {
			$exists_stmt = $this->prepare("SELECT ID FROM `penguins` WHERE ID = :ID");
			$exists_stmt->bindValue(":ID", $id);
			$exists_stmt->execute();
			$row_count = $exists_stmt->rowCount();
			$exists_stmt->closeCursor();
			
			return $row_count > 0;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function usernameExists($username) {
		try {
			$exists_stmt = $this->prepare("SELECT ID FROM `penguins` WHERE Username = :Username");
			$exists_stmt->bindValue(":Username", $username);
			$exists_stmt->execute();
			$row_count = $exists_stmt->rowCount();
			$exists_stmt->closeCursor();
			
			return $row_count > 0;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function getColumnsByName($username, array $columns) {
		try {
			$columns_string = implode(', ', $columns);
			$columns_stmt = $this->prepare("SELECT $columns_string FROM `penguins` WHERE Username = :Username");
			$columns_stmt->bindValue(":Username", $username);
			$columns_stmt->execute();
			$penguin_columns = $columns_stmt->fetch(\PDO::FETCH_ASSOC);
			$columns_stmt->closeCursor();
			
			return $penguin_columns;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function getColumnsById($id, array $columns) {
		try {
			$columns_string = implode(', ', $columns);
			$columns_stmt = $this->prepare("SELECT $columns_string FROM `penguins` WHERE ID = :ID");
			$columns_stmt->bindValue(":ID", $id);
			$columns_stmt->execute();
			$penguin_columns = $columns_stmt->fetch(\PDO::FETCH_ASSOC);
			$columns_stmt->closeCursor();
			
			return $penguin_columns;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function updateColumnById($id, $column, $value) {
		try {
			$update_stmt = $this->prepare("UPDATE `penguins` SET $column = :Value WHERE ID = :ID");
			$update_stmt->bindValue(":Value", $value);
			$update_stmt->bindValue(":ID", $id);
			$update_stmt->execute();
			$update_stmt->closeCursor();
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function getColumnById($id, $column) {
		try {
			$get_stmt = $this->prepare("SELECT $column FROM `penguins` WHERE ID = :ID");
			$get_stmt->bindValue(":ID", $id);
			$get_stmt->execute();
			$get_stmt->bindColumn($column, $value);
			$get_stmt->fetch(\PDO::FETCH_BOUND);
			$get_stmt->closeCursor();
			
			return $value;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
}

?>