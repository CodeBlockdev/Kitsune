<?php

namespace Kitsune\ClubPenguin;

use Kitsune;
use Kitsune\Logging\Logger;
use Kitsune\ClubPenguin\Packets\Packet;

abstract class ClubPenguin extends Kitsune\Kitsune {

	private $xmlHandlers = array(
		"policy" => "handlePolicyRequest",
		"verChk" => "handleVersionCheck",
		"rndK" => "handleRandomKey",
		"login" => "handleLogin"
	);
	
	protected $worldHandlers = array(
		// Overridden in the World class
	);
	
	private function handlePolicyRequest($socket) {
		$this->penguins[$socket]->send("<cross-domain-policy><allow-access-from domain='*' to-ports='{$this->port}' /></cross-domain-policy>");
	}
	
	private function handleVersionCheck($socket) {
		if(Packet::$Data["body"]["ver"]["@attributes"]["v"] == 153) {
			$this->penguins[$socket]->send("<msg t='sys'><body action='apiOK' r='0'></body></msg>");
		} else {
			$this->penguins[$socket]->send("<msg t='sys'><body action='apiKO' r='0'></body></msg>");
		}
	}
	
	private function handleRandomKey($socket) {
		$penguin = $this->penguins[$socket];
		$penguin->randomKey = Hashing::generateRandomKey();
		$penguin->send("<msg t='sys'><body action='rndK' r='-1'><k>" . $penguin->randomKey . "</k></body></msg>");
	}
	
	abstract protected function handleLogin($socket);
	
	protected function handleXmlPacket($socket) {
		if(array_key_exists(Packet::$Handler, $this->xmlHandlers)) {
			$method = $this->xmlHandlers[Packet::$Handler];
			call_user_func(array($this, $method), $socket);
		} else {
			$xmlPacket = Packet::GetInstance();
			
			Logger::Warn("Method for {$xmlPacket::$Handler} not found!");
		}
	}
	
	protected function handleWorldPacket($socket) {
		if($this->penguins[$socket]->identified == true) {
			$worldPacket = Packet::GetInstance();
			
			if(isset($this->worldHandlers[$worldPacket::$Extension])) {
				if(!empty($this->worldHandlers[$worldPacket::$Extension])) {
					if(isset($this->worldHandlers[$worldPacket::$Extension][$worldPacket::$Handler])) {
						if(method_exists($this, $this->worldHandlers[$worldPacket::$Extension][$worldPacket::$Handler])) {
							call_user_func(array($this, $this->worldHandlers[$worldPacket::$Extension][$worldPacket::$Handler]), $socket);
						} else {
							Logger::Warn("Method for {$worldPacket::$Extension}%{$worldPacket::$Handler} is un-callable!");
						}
					} else {
						Logger::Warn("Method for {$worldPacket::$Extension}%{$worldPacket::$Handler} doesn't exist/has not been set");
					}
				} else {
					Logger::Warn("There are no handlers for {$worldPacket::$Extension}");
				}
			} else {
				Logger::Warn("The packet extension '{$worldPacket::$Extension}' is not handled");
			}
		} else {
			$this->removePenguin($this->penguins[$socket]);
		}
	}
	
}

?>