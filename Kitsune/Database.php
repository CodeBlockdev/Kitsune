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
	
	public function updatePuffleColumn($puffle_id, $column, $value) {
		try {
			$update_puffle_column = $this->prepare("UPDATE `puffles` SET $column = :Value WHERE ID = :ID");
			$update_puffle_column->bindValue(":Value", $value);
			$update_puffle_column->bindValue(":ID", $puffle_id);
			$update_puffle_column->execute();
			$update_puffle_column->closeCursor();
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function puffleExists($puffle_id) {
		try {
			$puffle_exists_stmt = $this->prepare("SELECT ID FROM `puffles` WHERE ID = :ID");
			$puffle_exists_stmt->bindValue(":ID", $puffle_id);
			$puffle_exists_stmt->execute();
			
			$row_count = $puffle_exists_stmt->rowCount();
			$puffle_exists_stmt->closeCursor();
			
			return $row_count > 0;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function getPuffles($owner_id, $igloo = false) {
		try {
			$get_puffles_stmt = $this->prepare("SELECT ID, Type, Subtype, Name, AdoptionDate, Food, Play, Rest, Clean, Hat FROM `puffles` WHERE Owner = :Owner");
			$get_puffles_stmt->bindValue(":Owner", $owner_id);
			$get_puffles_stmt->execute();
			
			$owned_puffles = $get_puffles_stmt->fetchAll(\PDO::FETCH_NUM);
			$get_puffles_stmt->closeCursor();
			
			$puffles = implode('%', array_map(
				function($puffle) use ($owner_id, $igloo) {
					$walking_puffle = $this->getColumnById($owner_id, "Walking");
					
					if($igloo === true && $puffle[0] == $walking_puffle) {
						return;
					}
					
					$puffle = implode('|', $puffle);
					if($igloo === true) {
						$puffle .= "|0|0|0|0";
					}
					
					return $puffle;
				},
				$owned_puffles));
			
			return $puffles;			
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function getPuffleColumns($puffle_id, array $columns) {
		try {
			$columns = implode(', ', $columns);
			$get_puffle_stmt = $this->prepare("SELECT $columns FROM `puffles` WHERE ID = :Puffle");
			$get_puffle_stmt->bindValue(":Puffle", $puffle_id);
			$get_puffle_stmt->execute();
			$columns = $get_puffle_stmt->fetch(\PDO::FETCH_ASSOC);
			$get_puffle_stmt->closeCursor();
			
			return $columns;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
		
	public function getPuffleColumn($puffle_id, $column) {
		try {
			$get_puffle_stmt = $this->prepare("SELECT $column FROM `puffles` WHERE ID = :Puffle");
			$get_puffle_stmt->bindValue(":Puffle", $puffle_id);
			$get_puffle_stmt->execute();
			$get_puffle_stmt->bindColumn($column, $value);
			$get_puffle_stmt->fetch(\PDO::FETCH_BOUND);
			$get_puffle_stmt->closeCursor();
			
			return $value;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function adoptPuffle($owner_id, $puffle_name, $puffle_type, $puffle_subtype) {
		try {
			$adopt_puffle_stmt = $this->prepare("INSERT INTO `puffles` (`ID`, `Owner`, `Name`, `AdoptionDate`, `Type`, `Subtype`, `Hat`, `Food`, `Play`, `Rest`, `Clean`) VALUES (NULL, :Owner, :Name, UNIX_TIMESTAMP(), :Type, :Subtype, '0', '100', '100', '100', '100');");
			$adopt_puffle_stmt->bindValue(":Owner", $owner_id);
			$adopt_puffle_stmt->bindValue(":Name", $puffle_name);
			$adopt_puffle_stmt->bindValue(":Type", $puffle_type);
			$adopt_puffle_stmt->bindValue(":Subtype", $puffle_subtype);
			$adopt_puffle_stmt->execute();
			$adopt_puffle_stmt->closeCursor();
			
			$puffle_id = $this->lastInsertId();
			return $puffle_id;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
	
	public function getTotalIglooLikes($owner_id) {
		try {
			include "Misc/array_column.php";
			$total_likes_stmt = $this->prepare("SELECT Likes FROM `igloos` WHERE Owner = :Owner");
			$total_likes_stmt->bindValue(":Owner", $owner_id);
			$total_likes_stmt->execute();
			
			$likes = $total_likes_stmt->fetchAll(\PDO::FETCH_ASSOC);
			$total_likes_stmt->closeCursor();
			
			$likes = array_column($likes, "Likes");
			
			$total_likes = 0;
			
			foreach($likes as $likes_json) {
				$igloo_likes = json_decode($likes_json, true);
				
				foreach($igloo_likes as $like) {
					$total_likes += $like["count"];
				}
			}
			
			return $total_likes;
			
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
			
	
	public function getUsernamesBySwid($swid_list) {
		try {
			$swids = explode(',', $swid_list);
			$usernames = array();
			
			foreach($swids as $swid) {
				$swid_username_stmt = $this->prepare("SELECT Username FROM `penguins` WHERE SWID = :Swid");
				$swid_username_stmt->bindValue(":Swid", $swid);
				$swid_username_stmt->execute();
				
				$row_count = $swid_username_stmt->rowCount();
				if($row_count !== 0) {
					$swid_username_stmt->bindColumn("Username", $username);
					$swid_username_stmt->fetch(\PDO::FETCH_BOUND);
				
					array_push($usernames, $username);
				}
				
				$swid_username_stmt->closeCursor();
			}
			
			return implode(',', $usernames);
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}				
	
	public function getIglooLikes($igloo_id) {
		try {
			$igloo_likes_stmt = $this->prepare("SELECT Likes FROM `igloos` WHERE ID = :Igloo");
			$igloo_likes_stmt->bindValue(":Igloo", $igloo_id);
			$igloo_likes_stmt->execute();
			$igloo_likes_stmt->bindColumn("Likes", $likes_json);
			$igloo_likes_stmt->fetch(\PDO::FETCH_BOUND);
			$igloo_likes_stmt->closeCursor();
			
			$likes = json_decode($likes_json, true);
			
			return $likes;
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
	
	public function getIglooColumn($igloo_id, $column) {
		try {
			$get_igloo_stmt = $this->prepare("SELECT $column FROM `igloos` WHERE ID = :Igloo");
			$get_igloo_stmt->bindValue(":Igloo", $igloo_id);
			$get_igloo_stmt->execute();
			$get_igloo_stmt->bindColumn($column, $value);
			$get_igloo_stmt->fetch(\PDO::FETCH_BOUND);
			$get_igloo_stmt->closeCursor();
			
			return $value;
		} catch(\PDOException $pdo_exception) {
			echo "{$pdo_exception->getMessage()}\n";
		}
	}
			
	
	public function getAllIglooLayouts($player_id) {
		try {
			include "Misc/array_column.php";
			
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
			
			$likes = json_decode($igloo_array["Likes"], true);
			
			$like_count = 0;
			foreach($likes as $like) {
				$like_count += $like["count"];
			}
			
			$igloo_details = $igloo_id;
			$igloo_details .= ':' . $slot_number;
			$igloo_details .= ':0';
			$igloo_details .= ':' . $igloo_array["Locked"];
			$igloo_details .= ':' . $igloo_array["Music"];
			$igloo_details .= ':' . $igloo_array["Floor"];
			$igloo_details .= ':' . $igloo_array["Location"];
			$igloo_details .= ':' . $igloo_array["Type"];
			$igloo_details .= ':' . $like_count; // Igloo likes!
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