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