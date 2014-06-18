<?php

namespace Kitsune\ClubPenguin;

use Kitsune\Logging\Logger;
use Kitsune\ClubPenguin\Packets\Packet;

final class World extends ClubPenguin {

	protected $worldHandlers = array(
		"s" => array(
			"j#js" => "handleJoinWorld",
			"j#jr" => "handleJoinRoom",
			"j#jp" => "handleJoinPlayerRoom",
			
			"i#gi" => "handleGetInventoryList",
			"i#ai" => "handleBuyInventory",
			
			"u#glr" => "handleGetLastRevision",
			"u#pbi" => "handleGetPlayerInfoById",
			"u#sp" => "handleSendPlayerMove",
			"u#sf" => "handleSendPlayerFrame",
			"u#h" => "handleSendHeartbeat",
			"u#sa" => "handleUpdatePlayerAction",
			"u#gabcms" => "handleGetABTestData", // Currently has no method
			"u#se" => "handleSendEmote",
			"u#sb" => "handlePlayerThrowBall",
			"u#gbffl" => "handleGetBestFriendsList",
			"u#pbsu" => "handlePlayerBySwidUsername",
			
			"l#mg" => "handleGetMail",
			"l#mst" => "handleMailStartEngine",
			
			"s#upc" => "handleSendUpdatePlayerClothing",
			"s#uph" => "handleSendUpdatePlayerClothing",
			"s#upf" => "handleSendUpdatePlayerClothing",
			"s#upn" => "handleSendUpdatePlayerClothing",
			"s#upb" => "handleSendUpdatePlayerClothing",
			"s#upa" => "handleSendUpdatePlayerClothing",
			"s#upe" => "handleSendUpdatePlayerClothing",
			"s#upp" => "handleSendUpdatePlayerClothing",
			"s#upl" => "handleSendUpdatePlayerClothing",
			
			"g#gii" => "handleGetFurnitureInventory",
			"g#gm" => "handleGetActiveIgloo",
			"g#ggd" => "handleGetGameData",			
			"g#aloc" => "handleBuyIglooLocation",
			"g#gail" => "handleGetAllIglooLayouts",
			"g#uic" => "handleUpdateIglooConfiguration",
			"g#af" => "handleBuyFurniture",
			"g#ag" => "handleSendBuyIglooFloor",
			"g#au" => "handleSendBuyIglooType",
			"g#al" => "handleAddIglooLayout",
			"g#pio" => "handleLoadIsPlayerIglooOpen",
			"g#cli" => "handleCanLikeIgloo",
			"g#uiss" => "handleUpdateIglooSlotSummary",
			"g#gr" => "handleGetOpenIglooList",
			"g#gili" => "handleGetIglooLikeBy",
			"g#li" => "handleLikeIgloo",
			
			"m#sm" => "handleSendMessage",
			
			"o#k" => "handleKickPlayerById",
			"o#m" => "handleMutePlayerById",
			
			"p#pg" => "handleGetPufflesByPlayerId",
			"p#checkpufflename" => "handleCheckPuffleNameWithResponse",
			"p#pn" => "handleAdoptPuffle",
			"p#pgmps" => "handleGetMyPuffleStats",
			"p#pw" => "handleSendPuffleWalk",
			"p#pufflewalkswap" => "handlePuffleSwap",
			"p#puffletrick" => "handlePuffleTrick",
			"p#puffleswap" => "handleSendChangePuffleRoom"
		)
	);

	public $rooms = array();
	public $items = array();
	public $locations = array();
	public $furniture = array();
	public $floors = array();
	public $igloos = array();
	
	public $spawnRooms = array();
	
	private $openIgloos = array();
	
	public function __construct() {
		if(is_dir("crumbs") === false) {
			mkdir("crumbs", 0777);
		}
		
		$downloadAndDecode = function($url) {
			$filename = basename($url, ".json");
			
			if(file_exists("crumbs/$filename.json")) {
				$jsonData = file_get_contents("crumbs/$filename.json");
			} else {
				$jsonData = file_get_contents($url);
				file_put_contents("crumbs/$filename.json", $jsonData);
			}
			
			$dataArray = json_decode($jsonData, true);
			return $dataArray;
		};
		
		$rooms = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/rooms.json");
		foreach($rooms as $room => $details) {
			$this->rooms[$room] = new Room($room, sizeof($this->rooms) + 1);
			unset($rooms[$room]);
		}
		
		$agentRooms = array(212, 323, 803);
		$rockhoppersShip = array(422); // Captain's quarters
		$ninjaRooms = array(320, 321, 324, 326);
		$hotelRooms = range(430, 434);
		
		$noSpawn = array_merge($agentRooms, $rockhoppersShip, $ninjaRooms, $hotelRooms);
		$this->spawnRooms = array_keys(
			array_filter($this->rooms, function($room) use ($noSpawn) {
				if(!in_array($room->externalId, $noSpawn) && $room->externalId <= 810) {
					return true;
				}
			})
		);
		
		$items = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/paper_items.json");
		foreach($items as $item) {
			$itemId = $item["paper_item_id"];
			$this->items[$itemId] = $item["cost"];
			unset($items[$itemId]);
		}
		
		$locations = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/igloo_locations.json");
		foreach($locations as $location) {
			$locationId = $location["igloo_location_id"];
			$this->locations[$locationId] = $location["cost"];
			unset($locations[$locationId]);
		}
		
		$furnitureList = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/furniture_items.json");
		foreach($furnitureList as $furniture) {
			$furnitureId = $furniture["furniture_item_id"];
			$this->furniture[$furnitureId] = $furniture["cost"];
			unset($furnitureList[$furnitureId]);
		}
		
		$floors = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/igloo_floors.json");
		foreach($floors as $floor) {
			$floorId = $floor["igloo_floor_id"];
			$this->floors[$floorId] = $floor["cost"];
			unset($floors[$floorId]);
		}
		
		$igloos = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/igloos.json");
		foreach($igloos as $iglooId => $igloo) {
			$cost = $igloo["cost"];
			$this->igloos[$iglooId] = $cost;
			unset($igloos[$iglooId]);
		}
		
		Logger::Fine("World server is online");
	}
	
	public function mutePlayer($targetPlayer, $moderatorUsername) {
		$targetPlayer->muted = true;
		
		Logger::Info("$moderatorUsername has muted {$targetPlayer->username}");
	}
	
	protected function handleMutePlayerById($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->moderator) {
			$playerId = Packet::$Data[2];
			
			if(is_numeric($playerId)) {
				$targetPlayer = $this->getPlayerById($playerId);
				if($targetPlayer !== null) {
					$this->mutePlayer($targetPlayer, $penguin->username);
				}
			}
		}
	}
	
	public function kickPlayer($targetPlayer, $moderatorUsername) {
		$targetPlayer->send("%xt%e%-1%5%");
		$this->removePenguin($targetPlayer);
		
		Logger::Info("$moderatorUsername kicked {$targetPlayer->username}");
	}
	
	public function getPlayerById($playerId) {
		foreach($this->penguins as $penguin) {
			if($penguin->id == $playerId) {
				return $penguin;
			}
		}
		
		return null;
	}
	
	protected function handleKickPlayerById($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->moderator) {
			$playerId = Packet::$Data[2];
			
			if(is_numeric($playerId)) {
				$targetPlayer = $this->getPlayerById($playerId);
				if($targetPlayer !== null) {
					$this->kickPlayer($targetPlayer, $penguin->username);
				}
			}
		}
	}
	
	protected function handleSendChangePuffleRoom($socket) {
		$penguin = $this->penguins[$socket];
		$puffleId = Packet::$Data[2];
		$roomType = Packet::$Data[3];
		
		if($roomType == "igloo" || $roomType == "backyard") {
			if($penguin->database->ownsPuffle($puffleId, $penguin->id)) {
				$toBackyard = intval($roomType == "backyard");
				$penguin->database->sendChangePuffleRoom($puffleId, $toBackyard);
				$penguin->send("%xt%puffleswap%{$penguin->room->internalId}%$puffleId%$roomType%");				
			}
		}
	}
	
	protected function handlePuffleTrick($socket) {
		$penguin = $this->penguins[$socket];
		$puffleTrick = Packet::$Data[2];
		
		if(is_numeric($puffleTrick)) {
			$penguin->room->send("%xt%puffletrick%{$penguin->room->internalId}%{$penguin->id}%$puffleTrick%");
		}
	}
	
	protected function handleLoadIsPlayerIglooOpen($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[2];
		
		$open = intval(isset($this->openIgloos[$playerId]));
		
		$penguin->send("%xt%pio%{$penguin->room->internalId}%$open%");
	}
	
	// Add check to make sure puffle belongs to the player
	protected function handleSendPuffleWalk($socket) {
		$penguin = $this->penguins[$socket];
		$puffleId = Packet::$Data[2];
		$walkBoolean = Packet::$Data[3];
		
		if(is_numeric($puffleId) && $penguin->database->ownsPuffle($puffleId, $penguin->id)) {
			if($walkBoolean == 0 || $walkBoolean == 1) {
				$penguin->walkPuffle($puffleId, $walkBoolean);
			}
			
			if($walkBoolean == 0) {
				$penguin->database->updateColumnById($penguin->id, "Walking", 0);
			} else {
				$penguin->database->updateColumnById($penguin->id, "Walking", $puffleId);
			}
		}
	}
	
	protected function handlePuffleSwap($socket) {
		$penguin = $this->penguins[$socket];
		$puffleId = Packet::$Data[2];
		
		if(is_numeric($puffleId) && $penguin->database->ownsPuffle($puffleId, $penguin->id)) {
			$puffle = $penguin->database->getPuffleColumns($puffleId, array("Type", "Subtype", "Hat"));
			$penguin->room->send("%xt%pufflewalkswap%{$penguin->room->internalId}%{$penguin->id}%$puffleId%{$puffle["Type"]}%{$puffle["Subtype"]}%1%{$puffle["Hat"]}%");
			$penguin->database->updateColumnById($penguin->id, "Walking", $puffleId);
			$penguin->walkingPuffle = $penguin->database->getPuffleColumns($puffleId, array("Type", "Subtype", "Hat") );
			$penguin->walkingPuffle = array_values($penguin->walkingPuffle);
			array_unshift($penguin->walkingPuffle, $puffleId);
		}
	}
	
	protected function handleGetMyPuffleStats($socket) {
		Logger::Warn("TODO: Work on handleGetMyPuffleStats");
	}
	
	// Check if they exceed puffle limit
	// Also check if types are valid!
	// Implement proper coin deduction
	protected function handleAdoptPuffle($socket) {
		$penguin = $this->penguins[$socket];
		$puffleType = Packet::$Data[2];
		$puffleName = ucfirst(Packet::$Data[3]);
		$puffleSubtype = Packet::$Data[4];
		
		// DB stuff
		if(is_numeric($puffleType) && is_numeric($puffleSubtype)) {
			$puffleId = $penguin->database->adoptPuffle($penguin->id, $puffleName, $puffleType, $puffleSubtype);
			$adoptionDate = time();
			$penguin->send("%xt%pn%{$penguin->room->internalId}%{$penguin->coins}%$puffleId|$puffleType|$puffleSubtype|$puffleName|$adoptionDate|100|100|100|100|0|0|0|1|%");
			$penguin->database->updateColumnById($penguin->id, "Walking", $puffleId);
			$penguin->walkingPuffle = $penguin->database->getPuffleColumns($puffleId, array("Type", "Subtype", "Hat") );
			$penguin->walkingPuffle = array_values($penguin->walkingPuffle);
			array_unshift($penguin->walkingPuffle, $puffleId);
		}
	}
	
	protected function handleCheckPuffleNameWithResponse($socket) {
		$penguin = $this->penguins[$socket];
		$puffleName = Packet::$Data[2];
		
		$penguin->send("%xt%checkpufflename%{$penguin->room->internalId}%$puffleName%1%");
	}
	
	protected function handleCanLikeIgloo($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[1];
		
		if($penguin->database->playerIdExists($playerId)) {
			$activeIgloo = $penguin->database->getColumnById($playerId, "Igloo");
			$likes = $penguin->database->getIglooLikes($activeIgloo);
			
			foreach($likes as $like) {
				if($like["id"] == $penguin->swid) {
					$likeTime = $like["time"];
					
					if($likeTime < strtotime("-1 day")) {
						$canLike = array(
							"canLike" => true,
							"periodicity" => "ScheduleDaily",
							"nextLike_msecs" => 0
						);
					} else {
						$timeRemaining = (time() - $likeTime) * 1000;
						
						$canLike = array(
							"canLike" => false,
							"periodicity" => "ScheduleDaily",
							"nextLike_msecs" => $timeRemaining
						);
					}
					
					$canLike = json_encode($canLike);
					$penguin->send("%xt%cli%{$penguin->room->internalId}%$activeIgloo%200%$canLike%");
					
					break;
				}
			}
		}
	}
	
	protected function handlePlayerBySwidUsername($socket) {
		$penguin = $this->penguins[$socket];
		$swidList = Packet::$Data[2];
		
		$usernameList = $penguin->database->getUsernamesBySwid($swidList);
		$penguin->send("%xt%pbsu%{$penguin->room->internalId}%$usernameList%");
	}
	
	protected function handleLikeIgloo($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[1];
		
		include "Misc/array_column.php";
		
		if($penguin->database->playerIdExists($playerId)) {
			$activeIgloo = $penguin->database->getColumnById($playerId, "Igloo");
			$iglooLikes = $penguin->database->getIglooLikes($activeIgloo);
			$swids = array_column($iglooLikes, "id");
			
			if(in_array($penguin->swid, $swids)) {
				foreach($iglooLikes as $likeIndex => $like) {
					if($like["id"] == $penguin->swid) {
						$like["count"] == ++$like["count"];
						$like["time"] = time();
						$iglooLikes[$likeIndex] = $like;
						
						break;
					}
				}
			} else {
				$like = array(
					"id" => $penguin->swid,
					"time" => time(),
					"count" => 1,
					"isFriend" => false // TODO: Implement buddies
				);
				
				array_push($iglooLikes, $like);
			}
			
			$iglooLikes = json_encode($iglooLikes);
			$penguin->database->updateIglooColumn($activeIgloo, "Likes", $iglooLikes);
		}
	}
	
	protected function handleGetIglooLikeBy($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[1];
		
		if($penguin->database->playerIdExists($playerId)) {
			$iglooId = $penguin->database->getColumnById($playerId, "Igloo");
			$iglooLikes = $penguin->database->getIglooLikes($iglooId);
			$totalLikes = $penguin->database->getTotalIglooLikes($playerId);
			
			$likes = array(
				"likedby" => array(
					"counts" => array(
						"count" => $totalLikes,
						"maxCount" => $totalLikes,
						"accumCount" => $totalLikes
					),
					"IDs" => $iglooLikes
				)
			);
			
			$likesJson = json_encode($likes);
			$penguin->send("%xt%gili%{$penguin->room->internalId}%{$iglooId}%200%$likesJson%");
		}
	}
	
	protected function handleGetOpenIglooList($socket) {
		$penguin = $this->penguins[$socket];
		$totalLikes = $penguin->database->getTotalIglooLikes($penguin->id);
		
		$openIgloos = implode('%', array_map(
			function($playerId, $username) use ($penguin, $totalLikes) {
				if($playerId == $penguin->id) {
					$likes = $totalLikes;
				} else {
					$likes = $penguin->database->getTotalIglooLikes($playerId);
				}
			
				return $playerId . '|' . $username . '|' . $likes . '|0|0';
			}, array_keys($this->openIgloos), $this->openIgloos));
		
		$penguin->send("%xt%gr%{$penguin->room->internalId}%$totalLikes%0%$openIgloos%");
	}
	
	protected function handleGetBestFriendsList($socket) {
		$penguin = $this->penguins[$socket];
		$penguin->send("%xt%gbffl%{$penguin->room->internalId}%");
	}
	
	// People can steal other people's igloos this way.. need to add checks to make sure they are the owners!
	protected function handleUpdateIglooSlotSummary($socket) {
		$penguin = $this->penguins[$socket];
		$activeIgloo = Packet::$Data[2];
		
		if(is_numeric($activeIgloo) && $penguin->database->iglooExists($activeIgloo)) {
			$penguin->activeIgloo = $activeIgloo;
			$penguin->database->updateColumnById($penguin->id, "Igloo", $activeIgloo);
			
			$rawSlotSummary = Packet::$Data[3];
			$slotSummary = explode(',', $rawSlotSummary);
			
			foreach($slotSummary as $summary) {
				list($iglooId, $locked) = explode('|', $summary);
				if(is_numeric($iglooId) && is_numeric($locked)) {
					if($penguin->database->iglooExists($iglooId)) {
						$penguin->database->updateIglooColumn($iglooId, "Locked", $locked);
						
						if($locked == 0 && $penguin->activeIgloo == $iglooId) {
							$this->openIgloos[$penguin->id] = $penguin->username;
						} elseif($locked == 1 && $penguin->activeIgloo == $iglooId) {
							unset($this->openIgloos[$penguin->id]);
						}
					}
				}
			}
			
			$iglooDetails = $penguin->database->getIglooDetails($activeIgloo);
			$penguin->room->send("%xt%uvi%{$penguin->room->internalId}%$activeIgloo%$iglooDetails%");
		}
	}
	
	protected function handlePlayerThrowBall($socket) {
		$penguin = $this->penguins[$socket];
		
		$x = Packet::$Data[2];
		$y = Packet::$Data[3];
		
		$penguin->room->send("%xt%sb%{$penguin->room->internalId}%{$penguin->id}%$x%$y%");
	}
	
	protected function handleSendEmote($socket) {
		$penguin = $this->penguins[$socket];
		$emoteId = Packet::$Data[2];
		
		$penguin->room->send("%xt%se%{$penguin->room->internalId}%{$penguin->id}%$emoteId%");
	}
	
	protected function handleSendMessage($socket) {
		$penguin = $this->penguins[$socket];
		
		if(!$penguin->muted) {
			$message = Packet::$Data[3];
			
			$penguin->room->send("%xt%sm%{$penguin->room->internalId}%{$penguin->id}%$message%");
		}
	}
	
	protected function handleAddIglooLayout($socket) {
		$penguin = $this->penguins[$socket];
		
		$layoutCount = $penguin->database->getLayoutCount($penguin->id);
		
		if($layoutCount < 3) {
			$iglooId = $penguin->database->addIglooLayout($penguin->id);
			$penguin->activeIgloo = $iglooId;
			$iglooDetails = $penguin->database->getIglooDetails($iglooId, ++$layoutCount);
			$penguin->send("%xt%al%{$penguin->room->internalId}%{$penguin->id}%$iglooDetails%");
		}
		
	}
	
	protected function handleSendBuyIglooType($socket) {
		$penguin = $this->penguins[$socket];
		$iglooId = Packet::$Data[2];
		
		if(!isset($this->igloos[$iglooId])) {
			return $penguin->send("%xt%e%-1%402%");
		} elseif(isset($penguin->igloos[$iglooId])) { // May not be right lol?
			return $penguin->send("%xt%e%-1%400%");
		}
		
		$cost = $this->igloos[$iglooId];
		if($penguin->coins < $cost) {
			return $penguin->send("%xt%e%-1%401%");
		} else {
			$penguin->buyIgloo($iglooId, $cost);
		}
	}
	
	protected function handleSendBuyIglooFloor($socket) {
		$penguin = $this->penguins[$socket];
		$floorId = Packet::$Data[2];
		
		if(!isset($this->floors[$floorId])) {
			return $penguin->send("%xt%e%-1%402%");
		} elseif(isset($penguin->floors[$floorId])) {
			return $penguin->send("%xt%e%-1%400%");
		}
		
		$cost = $this->floors[$floorId];
		if($penguin->coins < $cost) {
			return $penguin->send("%xt%e%-1%401%");
		} else {
			$penguin->buyFloor($floorId, $cost);
		}
		
	}
		
	protected function handleBuyFurniture($socket) {
		$penguin = $this->penguins[$socket];
		$furnitureId = Packet::$Data[2];
		
		if(!isset($this->furniture[$furnitureId])) {
			return $penguin->send("%xt%e%-1%402%");
		}
		
		$cost = $this->furniture[$furnitureId];
		if($penguin->coins < $cost) {
			return $penguin->send("%xt%e%-1%401%");
		} else {
			$penguin->buyFurniture($furnitureId, $cost);
		}
	}
	
	// Need to add checks to make sure they are the owner!
	protected function handleUpdateIglooConfiguration($socket) {
		$penguin = $this->penguins[$socket];
		
		$activeIgloo = Packet::$Data[2];
		if(is_numeric($activeIgloo) && $penguin->database->ownsIgloo($activeIgloo, $penguin->id)) {
			$iglooType = Packet::$Data[3];
			$floor = Packet::$Data[4];
			$location = Packet::$Data[5];
			$music = Packet::$Data[6];
			$furniture = Packet::$Data[7];
			
			if(is_numeric($iglooType) && is_numeric($floor) && is_numeric($location) && is_numeric($music) && (strstr($furniture, ',') !== false)) {
				$penguin->activeIgloo = $activeIgloo;
				$penguin->database->updateColumnById($penguin->id, "Igloo", $penguin->activeIgloo);
				$penguin->database->updateIglooColumn($penguin->activeIgloo, "Type", $iglooType);
				$penguin->database->updateIglooColumn($penguin->activeIgloo, "Floor", $floor);
				$penguin->database->updateIglooColumn($penguin->activeIgloo, "Location", $location);
				$penguin->database->updateIglooColumn($penguin->activeIgloo, "Music", $music);
				$penguin->database->updateIglooColumn($penguin->activeIgloo, "Furniture", $furniture);
				
				$penguin->send("%xt%uic%{$penguin->room->internalId}%{$penguin->id}%{$penguin->activeIgloo}%$iglooType:$floor:$location:$music:$furniture%");
				
				$iglooDetails = $penguin->database->getIglooDetails($activeIgloo);
				$penguin->room->send("%xt%uvi%{$penguin->room->internalId}%$activeIgloo%$iglooDetails%");
			}
		}
	}
	
	// Should use $penguin->id instead of Packet::$Data[2].. ?
	protected function handleGetAllIglooLayouts($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[2];
		
		if($penguin->database->playerIdExists($playerId)) {
			$iglooLayouts = $penguin->database->getAllIglooLayouts($playerId);
			$activeIgloo = $penguin->database->getColumnById($playerId, "Igloo");
			$totalLikes = $penguin->database->getTotalIglooLikes($playerId);
			
			$penguin->send("%xt%gail%{$penguin->room->internalId}%$playerId%$activeIgloo%$iglooLayouts%");
			$penguin->send("%xt%gaili%{$penguin->room->internalId}%$totalLikes%%");
			
		}
	}
	
	protected function handleBuyIglooLocation($socket) {
		$penguin = $this->penguins[$socket];
		$locationId = Packet::$Data[2];
		
		if(!isset($this->locations[$locationId])) {
			return $penguin->send("%xt%e%-1%402%");
		} elseif(isset($penguin->locations[$locationId])) {
			return $penguin->send("%xt%e%-1%400%");
		}
		
		$cost = $this->locations[$locationId];
		if($penguin->coins < $cost) {
			return $penguin->send("%xt%e%-1%401%");
		} else {
			$penguin->buyLocation($locationId, $cost);
		}
	}
	
	protected function handleJoinPlayerRoom($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[2];
		$roomType = Packet::$Data[3];
		
		if($penguin->database->playerIdExists($playerId)) {
			$externalId = $playerId + 1000;
			
			if(!isset($this->rooms[$externalId])) {
				$this->rooms[$externalId] = new Room($externalId, $playerId);
			}
			
			$penguin->send("%xt%jp%$playerId%$playerId%$externalId%$roomType%");
			$this->joinRoom($penguin, $externalId);
		}
	}
	
	protected function handleGetPufflesByPlayerId($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[2];
		$roomType = Packet::$Data[3];
		
		if($penguin->database->playerIdExists($playerId)) {
			$puffles = $penguin->database->getPuffles($playerId, $roomType);
			
			// Doesn't necessarily have to be $playerId, I think?
			$penguin->send("%xt%pg%{$penguin->room->internalId}%$playerId%$puffles%$roomType%");
		}
	}
	
	protected function handleGetGameData($socket) {
		$penguin = $this->penguins[$socket];
		$penguin->send("%xt%ggd%{$penguin->room->internalId}%Kitsune%");
	}
	
	protected function handleGetActiveIgloo($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[2];
		
		if($penguin->database->playerIdExists($playerId)) {
			$activeIgloo = $penguin->database->getColumnById($playerId, "Igloo");
			
			if($playerId == $penguin->id) {
				$penguin->activeIgloo = $activeIgloo;
			}
			
			$iglooDetails = $penguin->database->getIglooDetails($activeIgloo);
			$penguin->send("%xt%gm%{$penguin->room->internalId}%$playerId%$iglooDetails%");
		}
	}
	
	protected function handleGetFurnitureInventory($socket) {
		$penguin = $this->penguins[$socket];
		
		$furnitureList = $penguin->database->getColumnsById($penguin->id, array("Furniture", "Floors", "Igloos", "Locations"));
		
		$furnitureArray = explode(',', $furnitureList["Furniture"]);
		foreach($furnitureArray as $furniture) {
			$furnitureDetails = explode('|', $furniture);
			list($furnitureId, $purchaseDate, $quantity) = $furnitureDetails;
			$penguin->furniture[$furnitureId] = $quantity;
		}
		
		$flooringArray = explode(',', $furnitureList["Floors"]);
		foreach($flooringArray as $flooring) {
			$flooringDetails = explode('|', $flooring);
			list($flooringId, $purchaseDate) = $flooringDetails;
			$penguin->floors[$flooringId] = $purchaseDate;
		}
		
		$igloosArray = explode(',', $furnitureList["Igloos"]);
		foreach($igloosArray as $igloo) {
			$iglooDetails = explode('|', $igloo);
			list($iglooType, $purchaseDate) = $iglooDetails;
			$penguin->igloos[$iglooType] = $purchaseDate;
		}
		
		$locationArray = explode(',', $furnitureList["Locations"]);
		foreach($locationArray as $location) {
			$locationDetails = explode('|', $location);
			list($locationId, $purchaseDate) = $locationDetails;
			$penguin->locations[$locationId] = $purchaseDate;
		}
		
		$furnitureList = implode('%', $furnitureList);
		$penguin->send("%xt%gii%{$penguin->room->internalId}%$furnitureList%");
	}
	
	// Because I'm super lazy
	protected function handleSendUpdatePlayerClothing($socket) {
		$penguin = $this->penguins[$socket];
		$itemId = Packet::$Data[2];
		$clothingType = substr(Packet::$Data[0], 2);
		$clothing = array(
			"upc" => "Color",
			"uph" => "Head",
			"upf" => "Face",
			"upn" => "Neck",
			"upb" => "Body",
			"upa" => "Hand",
			"upe" => "Feet",
			"upp" => "Photo",
			"upl" => "Flag"
		);
			
		call_user_func(array($penguin, "update{$clothing[$clothingType]}"), $itemId);
	}
	
	protected function handleBuyInventory($socket) {
		$penguin = $this->penguins[$socket];
		$itemId = Packet::$Data[2];
		
		if(!isset($this->items[$itemId])) {
			return $penguin->send("%xt%e%-1%402%");
		} elseif(isset($this->penguin->inventory[$itemId])) {
			return $penguin->send("%xt%e%-1%400%");
		}
		
		$cost = $this->items[$itemId];
		if($penguin->coins < $cost) {
			return $penguin->send("%xt%e%-1%401%");
		} else {
			$penguin->addItem($itemId, $cost);
		}
	}
	
	protected function handleUpdatePlayerAction($socket) {
		$penguin = $this->penguins[$socket];
		$actionId = Packet::$Data[2];
		
		$penguin->room->send("%xt%sa%{$penguin->room->internalId}%{$penguin->id}%{$actionId}%");
	}
	
	protected function handleSendHeartbeat($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->send("%xt%h%{$penguin->room->internalId}%");
	}
	
	protected function handleSendPlayerFrame($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->frame = Packet::$Data[2];
		$penguin->room->send("%xt%sf%{$penguin->room->internalId}%{$penguin->id}%{$penguin->frame}%");
	}
		
	protected function handleSendPlayerMove($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->x = Packet::$Data[2];
		$penguin->y = Packet::$Data[3];
		$penguin->room->send("%xt%sp%{$penguin->room->internalId}%{$penguin->id}%{$penguin->x}%{$penguin->y}%"); 
	}
	
	protected function handleGetPlayerInfoById($socket) {
		$penguin = $this->penguins[$socket];
		$penguinId = Packet::$Data[2];
		
		if($penguin->database->playerIdExists($penguinId)) {
			$playerArray = $penguin->database->getColumnsById($penguinId, array("Username", "SWID"));
			$penguin->send("%xt%pbi%{$penguin->room->internalId}%{$playerArray["SWID"]}%$penguinId%{$playerArray["Username"]}%");
		}	
	}
	
	protected function handleJoinRoom($socket) {
		$penguin = $this->penguins[$socket];
		
		$room = Packet::$Data[2];
		$x = Packet::$Data[3];
		$y = Packet::$Data[3];
		
		$this->joinRoom($penguin, $room, $x, $y);
	}
	
	protected function handleGetABTestData($socket) {
		
	}
	
	protected function handleGetMail($socket) {
		$this->penguins[$socket]->send("%xt%mg%-1%");
	}
	
	protected function handleGetLastRevision($socket) {
		$this->penguins[$socket]->send("%xt%glr%-1%10915%");
	}
	
	protected function handleMailStartEngine($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->send("%xt%mst%-1%0%0%");
	}
	
	protected function handleGetInventoryList($socket) {
		$penguin = $this->penguins[$socket];
		
		$inventoryList = implode('%', $penguin->inventory);
		$penguin->send("%xt%gi%-1%$inventoryList%");
	}
	
	public function joinRoom($penguin, $roomId, $x = 0, $y = 0) {
		if(!isset($this->rooms[$roomId])) {
			return;
		} elseif(isset($penguin->room)) {
			$penguin->room->remove($penguin);
		}
		
		$penguin->frame = 1;
		$penguin->x = $x;
		$penguin->y = $y;
		$this->rooms[$roomId]->add($penguin);
	}
	
	private function getOpenRoom() {
		$spawnRooms = $this->spawnRooms;
		shuffle($spawnRooms);
		
		foreach($spawnRooms as $roomId) {
			if(sizeof($this->rooms[$roomId]->penguins) < 75) {
				return $roomId;
			}
		}
		
		return 100;
	}
	
	protected function handleJoinWorld($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->id != Packet::$Data[2]) {
			return $this->removePenguin($penguin);
		}
		
		$loginKey = Packet::$Data[3];
		$dbLoginKey = $penguin->database->getColumnById($penguin->id, "LoginKey");
		
		if($dbLoginKey != $loginKey) {
			$penguin->send("%xt%e%-1%101%");
			$penguin->database->updateColumnByid($penguin->id, "LoginKey", "");
			return $this->removePenguin($penguin);
		}
		
		$penguin->database->updateColumnByid($penguin->id, "LoginKey", "");
		
		$penguin->loadPlayer();
		
		$penguin->send("%xt%activefeatures%-1%");
		
		$isModerator = intval($penguin->moderator);
		$penguin->send("%xt%js%-1%1%0%$isModerator%1%");
		$penguin->send("%xt%gps%-1%{$penguin->id}%");
		
		$puffles = $penguin->database->getPlayerPuffles($penguin->id);
		$penguin->send("%xt%pgu%-1%$puffles%");
		
		$playerString = $penguin->getPlayerString();
		$loginTime = time(); // ?
		
		$loadPlayer = "$playerString|%{$penguin->coins}%0%1440%$loginTime%{$penguin->age}%0%7521%%7%1%0%211843";
		$penguin->send("%xt%lp%-1%$loadPlayer%");
		
		$openRoom = $this->getOpenRoom();
		$this->joinRoom($penguin, $openRoom, 0, 0);
				
		// The 0 after the player id is probably a transformation id, will be looking into a proper implementation
		$penguin->room->send("%xt%spts%-1%{$penguin->id}%0%{$penguin->avatar}%");
		
		$penguin->send("%xt%cberror%-1%Kitsune is a Club Penguin private server program written in PHP by Arthur designed to emulate the AS3 protocol.%Welcome%");
	}

	protected function handleLogin($socket) {
		$penguin = $this->penguins[$socket];
		$rawPlayerString = Packet::$Data['body']['login']['nick'];
		$playerHashes = Packet::$Data['body']['login']['pword'];
		
		$playerArray = explode('|', $rawPlayerString);
		list($id, $swid, $username) = $playerArray;
		
		if(!$penguin->database->playerIdExists($id)) {
			return $this->removePenguin($penguin);
		}
		
		if(!$penguin->database->usernameExists($username)) {
			$penguin->send("%xt%e%-1%101%");
			return $this->removePenguin($penguin);
		}
		
		$hashesArray = explode('#', $playerHashes);
		list($loginKey, $confirmationHash) = $hashesArray;
		
		$dbConfirmationHash = $penguin->database->getColumnById($id, "ConfirmationHash");
		if($dbConfirmationHash != $confirmationHash) {
			$penguin->send("%xt%e%-1%101%");
			return $this->removePenguin($penguin);
		} else {
			$penguin->database->updateColumnByid($id, "ConfirmationHash", ""); // Maybe the column should be cleared even when the login is unsuccessful
			$penguin->id = $id;
			$penguin->swid = $swid;
			$penguin->username = $username;
			$penguin->identified = true;
			$penguin->send("%xt%l%-1%");
		}
		
	}
	
	protected function removePenguin($penguin) {
		$this->removeClient($penguin->socket);
		
		if($penguin->room !== null) {
			$penguin->room->remove($penguin);
		}
		
		unset($this->penguins[$penguin->socket]);
	}
	
	protected function handleDisconnect($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->room !== null) {
			$penguin->room->remove($penguin);
		}
		
		unset($this->penguins[$socket]);
		
		Logger::Info("Player disconnected");
	}
	
}

?>