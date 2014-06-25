<?php

namespace Kitsune\ClubPenguin;

use Kitsune;
use Kitsune\Logging\Logger;
use Kitsune\ClubPenguin\Packets\Packet;
use Kitsune\ClubPenguin\Plugins\Base\IPlugin as Plugin;

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
	
	protected $loadedPlugins = array();
	
	protected function __construct($loadPlugins = true, $pluginsDirectory = "Kitsune/ClubPenguin/Plugins/") {
		if($loadPlugins === true) {
			$this->loadPlugins($pluginsDirectory);
		}
	}
	
	public function checkPluginDependencies() {
		foreach($this->loadedPlugins as $pluginClass => $pluginObject) {
			if(!empty($pluginObject->dependencies)) {
				foreach($pluginObject->dependencies as $pluginDependency) {
					if(!isset($this->loadedPlugins[$pluginDependency])) {
						Logger::Warn("Depdency '$pluginDependency' for plugin '$pluginClass' not loaded!");
						unset($this->loadedPlugins[$pluginClass]);
					}
				}
			}
		}
	}
	
	public function loadPlugin($pluginClass, $pluginNamespace) {
		$pluginPath = sprintf("%s\%s", $pluginNamespace, $pluginClass);
		
		$pluginObject = new $pluginPath($this);
		$this->loadedPlugins[$pluginClass] = $pluginObject;
		
		if(!empty($pluginObject->xmlHandlers)) {
			foreach($pluginObject->xmlHandlers as $xmlHandler => $handlerProperties) {
				list($handlerCallback, $callInformation) = $handlerProperties;
				
				if($callInformation == Plugin::Override) {
					$this->xmlHandlers[$xmlHandler] = array($pluginObject, $handlerCallback);
				}
			}
		}
		
		if(!empty($pluginObject->worldHandlers)) {
			foreach($pluginObject->worldHandlers as $packetExtension => $extensionHandlers) {
				foreach($extensionHandlers as $packetHandler => $handlerProperties) {
					list($handlerCallback, $callInformation) = $handlerProperties;
					
					if($callInformation == Plugin::Override) {
						$this->worldHandlers[$packetExtension][$packetHandler] = array($pluginObject, $handlerCallback);
					}
				}
			}
		}
	}
	
	public function loadPluginFolder($pluginFolder) {
		$pluginNamespace = str_replace("/", "\\", $pluginFolder);
		$pluginNamespace = rtrim($pluginNamespace, "\\");
		
		$pluginFiles = scandir($pluginFolder);
		$pluginFiles = array_splice($pluginFiles, 2);
		
		// Filter directories using array_map
		$pluginFolders = array_map(
			function($pluginFile) use($pluginFolder) {
				$lePath = sprintf("%s%s", $pluginFolder, $pluginFile);
				
				if(is_dir($lePath)) {					
					return $lePath;
				}
			}, $pluginFiles
		);
		
		$pluginFiles = array_diff($pluginFiles, $pluginFolders);
		
		$pluginClasses = array_map(
			function($pluginFile) {
				return basename($pluginFile, ".php");
			}, $pluginFiles
		);
		
		// Load plugins by class
		foreach($pluginClasses as $pluginClass) {
			if(!isset($this->loadedPlugins[$pluginClass])) {
				$this->loadPlugin($pluginClass, $pluginNamespace);
			}
		}
		
		// Load plugin folders
		foreach($pluginFolders as $pluginFolder) {
			if($pluginFolder !== null) {
				$this->loadPluginFolder($pluginFolder);
			}
		}
	}
	
	public function loadPlugins($pluginsDirectory) {
		if(!is_dir($pluginsDirectory)) {
			Logger::Error("Plugins directory ($pluginsDirectory) does not exist!");
		} else {
			Logger::Info("Loading plugins");
			
			$pluginFolders = scandir($pluginsDirectory);
			$pluginFolders = array_splice($pluginFolders, 2);
			
			$pluginFolders = array_filter($pluginFolders,
				function($pluginFolder) {
					if($pluginFolder != "Base") {
						return true;
					}
				}
			);
			
			foreach($pluginFolders as $pluginFolder) {
				$folderPath = sprintf("%s%s", $pluginsDirectory, $pluginFolder);
				
				$this->loadPluginFolder($folderPath);
			}
			
			// Check dependencies
			$this->checkPluginDependencies();
			
			Logger::Info(sprintf("Loaded %d plugin(s)", sizeof($this->loadedPlugins)));
		}
	}
	
	protected function handlePolicyRequest($socket) {
		$this->penguins[$socket]->send("<cross-domain-policy><allow-access-from domain='*' to-ports='{$this->port}' /></cross-domain-policy>");
	}
	
	protected function handleVersionCheck($socket) {
		if(Packet::$Data["body"]["ver"]["@attributes"]["v"] == 153) {
			$this->penguins[$socket]->send("<msg t='sys'><body action='apiOK' r='0'></body></msg>");
		} else {
			$this->penguins[$socket]->send("<msg t='sys'><body action='apiKO' r='0'></body></msg>");
		}
	}
	
	protected function handleRandomKey($socket) {
		$penguin = $this->penguins[$socket];
		$penguin->randomKey = Hashing::generateRandomKey();
		$penguin->send("<msg t='sys'><body action='rndK' r='-1'><k>" . $penguin->randomKey . "</k></body></msg>");
	}
	
	abstract protected function handleLogin($socket);
	
	protected function handleXmlPacket($socket) {
		$xmlPacket = Packet::GetInstance();
		
		if(array_key_exists($xmlPacket::$Handler, $this->xmlHandlers)) {
			$method = $this->xmlHandlers[$xmlPacket::$Handler];
			call_user_func(array($this, $method), $socket);
			
			foreach($this->loadedPlugins as $loadedPlugin) {
				if(!empty($loadedPlugin->xmlHandlers)) {
					$loadedPlugin->handleXmlPacket($this->penguins[$socket]);
				}
			}
		} else {		
			Logger::Warn("Method for {$xmlPacket::$Handler} not found!");
		}
	}
	
	protected function handleWorldPacket($socket) {
		if($this->penguins[$socket]->identified == true) {
			$worldPacket = Packet::GetInstance();
			
			$penguin = $this->penguins[$socket];
			
			foreach($this->loadedPlugins as $loadedPlugin) {
				if($loadedPlugin->worldStalker) {
					$loadedPlugin->handleWorldPacket($penguin);
				} elseif(isset($loadedPlugin->worldHandlers[$worldPacket::$Extension][$worldPacket::$Handler])) {
					list($handlerCallback, $callInformation) = $loadedPlugin->worldHandlers[$worldPacket::$Extension][$worldPacket::$Handler];
					
					if($callInformation == Plugin::Before || $callInformation == Plugin::Both) {
						$loadedPlugin->handleWorldPacket($penguin);
					}
				}
			}
			
			if(isset($this->worldHandlers[$worldPacket::$Extension])) {
				if(!empty($this->worldHandlers[$worldPacket::$Extension])) {
					if(isset($this->worldHandlers[$worldPacket::$Extension][$worldPacket::$Handler])) {
						$handlerCallback = $this->worldHandlers[$worldPacket::$Extension][$worldPacket::$Handler];
						
						if(is_array($handlerCallback)) {
							call_user_func($handlerCallback, $penguin);
						} elseif(method_exists($this, $handlerCallback)) {
							call_user_func(array($this, $handlerCallback), $socket);
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
			
			foreach($this->loadedPlugins as $loadedPlugin) {
				if($loadedPlugin->worldStalker) {
					$loadedPlugin->handleWorldPacket($penguin, false);
				} elseif(isset($loadedPlugin->worldHandlers[$worldPacket::$Extension][$worldPacket::$Handler])) {
					list($handlerCallback, $callInformation) = $loadedPlugin->worldHandlers[$worldPacket::$Extension][$worldPacket::$Handler];
					
					if($callInformation == Plugin::After || $callInformation == Plugin::Both) {
						$loadedPlugin->handleWorldPacket($penguin, false);
					}
				}
			}
		} else {
			$this->removePenguin($this->penguins[$socket]);
		}
	}
	
}

?>