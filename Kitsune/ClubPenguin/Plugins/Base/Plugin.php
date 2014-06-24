<?php

namespace Kitsune\ClubPenguin\Plugins\Base;

use Kitsune\Logging\Logger;
use Kitsune\ClubPenguin\Packets\Packet;

abstract class Plugin implements IPlugin {

	public $dependencies = array();
	
	public $worldHandlers = array();
	
	public $xmlHandlers = array();
	
	protected $server = null;
	
	private $pluginName;
	
	protected function __construct($pluginName) {
		if($this->server == null) {
			Logger::Warn("Plugin didn't set a server object");
		}
		
		$this->pluginName = $pluginName;
	}
	
	public function handleXmlPacket($penguin) {
		
	}
	
	public function handleWorldPacket($penguin) {
		if(isset($this->worldHandlers[Packet::$Extension]) && isset($this->worldHandlers[Packet::$Extension][Packet::$Handler])) {
			$methodName = $this->worldHandlers[Packet::$Extension][Packet::$Handler];
			
			if(method_exists($this, $methodName)) {
				call_user_func(array($this, $methodName), $penguin);
			} else {
				Logger::Warn("Method '$methodName' doesn't exist in plugin '{$this->pluginName}'");
			}
		}
	}
	
}

?>