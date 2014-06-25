<?php

namespace Kitsune\ClubPenguin\Plugins\Ranks;

use Kitsune\Database;
use Kitsune\ClubPenguin\Packets\Packet;
use Kitsune\ClubPenguin\Plugins\Base\Plugin;

final class Ranks extends Plugin {
	
	public $worldHandlers = array(
		"s" => array(
			"j#js" => array("prepareWorld", self::Before)
		)
	);
	
	private $database;
	
	public function __construct($server) {
		$this->server = $server;
		
		$this->database = new Database();
		
		parent::__construct(__CLASS__);
	}
	
	public function prepareWorld($penguin) {
		
	}
	
}

?>