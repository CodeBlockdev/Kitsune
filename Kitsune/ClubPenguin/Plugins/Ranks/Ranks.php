<?php

namespace Kitsune\ClubPenguin\Plugins\Ranks;

use Kitsune\Database;
use Kitsune\Logging\Logger;
use Kitsune\ClubPenguin\Packets\Packet;
use Kitsune\ClubPenguin\Plugins\Base\Plugin;

final class Ranks extends Plugin {
	
	public $worldHandlers = array(
		"s" => array(
			"j#js" => array("setMembershipDays", self::After)
		)
	);
	
	public $xmlHandlers = array(null);
	
	private $database;
	
	public function __construct($server) {
		$this->server = $server;
	}
	
	public function onReady() {
		$this->database = new Database();
		
		$simpleXml = simplexml_load_file("Database.xml");
		$databaseName = $simpleXml->name;
		unset($simpleXml);
		
		try {
			$rankColumn = $this->database->prepare("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :Database AND TABLE_NAME = 'penguins' AND COLUMN_NAME = 'Rank'");
			$rankColumn->bindValue(":Database", $databaseName);
			$rankColumn->execute();
			
			$rowCount = $rankColumn->rowCount();
			$rankColumn->closeCursor();
			
			if($rowCount == 0) {				
				$rankColumn = $this->database->prepare("ALTER TABLE `penguins` ADD `Rank` TINYINT UNSIGNED NOT NULL DEFAULT '1' AFTER `Moderator`");
				$rankColumn->execute();
				$rankColumn->closeCursor();
				
				Logger::Notice("Rank column created");
			}
		} catch(\PDOException $pdoException) {
			Logger::Warn($pdoException->getMessage());
		}
		
		parent::__construct(__CLASS__);
	}
	
	private function determineMembershipDays($rankLevel) {
		switch($rankLevel) {
			case 1:	return 1;
			case 2:	return 700;
			case 3:	return 2000;
		}
	}
	
	public function setMembershipDays($penguin) {
		$getRank = $this->database->prepare("SELECT Rank FROM `penguins` WHERE ID = :ID");
		$getRank->bindValue(":ID", $penguin->id);
		$getRank->execute();
		list($rankLevel) = $getRank->fetch(\PDO::FETCH_NUM);
		$getRank->closeCursor();
		
		$penguin->membershipDays = $this->determineMembershipDays($rankLevel);
	}
	
}

?>