<?php

namespace Kitsune\ClubPenguin\Plugins\Commands;

use Kitsune\ClubPenguin\Packets\Packet;
use Kitsune\ClubPenguin\Plugins\Base\Plugin;

final class Commands extends Plugin {
	
	public $dependencies = array("Ranks");
	
	public $worldHandlers = array(
		"s" => array(
			"m#sm" => array("handlePlayerMessage", self::Both)
		)
	);
	
	private $commandPrefixes = array("!", "/");
	
	private $commands = array(
		"AI" => "buyItem",
		"JR" => "joinRoom"
	);
	
	private $mutedPenguins = array();
	
	public function __construct($server) {
		$this->server = $server;
		
		parent::__construct(__CLASS__);
	}
	
	private function buyItem($penguin, $arguments) {
		list($itemId) = $arguments;
		
		if(isset($this->server->items[$itemId])) {
			$penguin->addItem($itemId, 0);
		}
	}
	
	private function joinRoom($penguin, $arguments) {
		list($roomId) = $arguments;
		
		$this->server->joinRoom($penguin, $roomId);
	}
	
	protected function handlePlayerMessage($penguin) {
		$message = Packet::$Data[3];
		
		$firstCharacter = substr($message, 0, 1);
		if(in_array($firstCharacter, $this->commandPrefixes)) {
			$messageParts = explode(" ", $message);
			
			$command = $messageParts[0];
			$command = substr($command, 1);
			$command = strtoupper($command);
			
			$arguments = array_splice($messageParts, 1);
			
			if(isset($this->commands[$command])) {
				if(in_array($penguin, $this->mutedPenguins)) {
					$penguin->muted = false;
				} else {
					$penguin->muted = true;
					array_push($this->mutedPenguins, $penguin);
					call_user_func(array($this, $this->commands[$command]), $penguin, $arguments);
				}
			}
		}
	}
	
}

?>