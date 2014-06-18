<?php

namespace Kitsune\ClubPenguin;

use Kitsune\ClubPenguin\Packets\Packet;

final class World extends ClubPenguin {

	protected $worldHandlers = array(
		"s" => array(
			"j#js" => "handleJoinWorld",
			"i#gi" => "handleGetInventoryList",
			"l#mst" => "handleMailStartEngine",
			"u#glr" => "handleGetLastRevision",
			"l#mg" => "handleGetMail",
			"u#gabcms" => "handleGetABTestData", // Currently has no method
			"j#jr" => "handleJoinRoom",
			"u#pbi" => "handleGetPlayerInfoById",
			"u#sp" => "handleSendPlayerMove",
			"u#sf" => "handleSendPlayerFrame",
			"u#h" => "handleSendHeartbeat",
			"u#sa" => "handleUpdatePlayerAction",
			"i#ai" => "handleBuyInventory",
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
			"p#pg" => "handleGetPufflesByPlayerId",
			"j#jp" => "handleJoinPlayerRoom",
			"g#aloc" => "handleBuyIglooLocation",
			"g#gail" => "handleGetAllIglooLayouts",
			"g#uic" => "handleUpdateIglooConfiguration",
			"g#af" => "handleBuyFurniture",
			"g#ag" => "handleSendBuyIglooFloor",
			"g#au" => "handleSendBuyIglooType",
			"g#al" => "handleAddIglooLayout",
			"g#pio" => "handleLoadIsPlayerIglooOpen",
			"m#sm" => "handleSendMessage",
			"u#se" => "handleSendEmote",
			"u#sb" => "handlePlayerThrowBall",
			"g#uiss" => "handleUpdateIglooSlotSummary",
			"u#gbffl" => "handleGetBestFriendsList",
			"g#gr" => "handleGetOpenIglooList",
			"g#gili" => "handleGetIglooLikeBy",
			"g#li" => "handleLikeIgloo",
			"u#pbsu" => "handlePlayerBySwidUsername",
			"g#cli" => "handleCanLikeIgloo",
			"p#checkpufflename" => "handleCheckPuffleNameWithResponse",
			"p#pn" => "handleAdoptPuffle",
			"p#pgmps" => "handleGetMyPuffleStats",
			"p#pw" => "handleSendPuffleWalk",
			"p#pufflewalkswap" => "handlePuffleSwap",
			"p#puffletrick" => "handlePuffleTrick",
			"p#puffleswap" => "handleSendChangePuffleRoom"
		)
	);
	
	private $rooms = array();
	private $items = array();
	private $locations = array();
	private $furniture = array();
	private $floors = array();
	private $igloos = array();
	
	private $open_igloos = array();
	
	public function __construct() {
		if(is_dir("crumbs") === false) {
			mkdir("crumbs", 0777);
		}
		
		$downloadAndDecode = function($url) {
			$filename = basename($url, ".json");
			
			if(file_exists("crumbs/$filename.json")) {
				$json_data = file_get_contents("crumbs/$filename.json");
			} else {
				$json_data = file_get_contents($url);
				file_put_contents("crumbs/$filename.json", $json_data);
			}
			
			$data_array = json_decode($json_data, true);
			return $data_array;
		};
		
		echo "Setting up rooms.. ";
		$rooms = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/rooms.json");
		foreach($rooms as $room => $details) {
			$this->rooms[$room] = new Room($room, sizeof($this->rooms) + 1);
			unset($rooms[$room]);
		}
		echo "done\n";
		
		echo "Building clothing list.. ";
		$items = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/paper_items.json");
		foreach($items as $item) {
			$item_id = $item["paper_item_id"];
			$this->items[$item_id] = $item["cost"];
			unset($items[$item_id]);
		}
		echo "done\n";
		
		echo "Building location catalogue.. ";
		$locations = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/igloo_locations.json");
		foreach($locations as $location) {
			$location_id = $location["igloo_location_id"];
			$this->locations[$location_id] = $location["cost"];
			unset($locations[$location_id]);
		}
		echo "done\n";
		
		echo "Building furniture catalogue.. ";
		$furniture_list = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/furniture_items.json");
		foreach($furniture_list as $furniture) {
			$furniture_id = $furniture["furniture_item_id"];
			$this->furniture[$furniture_id] = $furniture["cost"];
			unset($furniture_list[$furniture_id]);
		}
		echo "done\n";
		
		echo "Building floor catalogue.. ";
		$floors = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/igloo_floors.json");
		foreach($floors as $floor) {
			$floor_id = $floor["igloo_floor_id"];
			$this->floors[$floor_id] = $floor["cost"];
			unset($floors[$floor_id]);
		}
		echo "done\n";
		
		echo "Building igloo catalogue.. ";
		$igloos = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/igloos.json");
		foreach($igloos as $igloo_id => $igloo) {
			$cost = $igloo["cost"];
			$this->igloos[$igloo_id] = $cost;
			unset($igloos[$igloo_id]);
		}
		echo "done\n";
	}
	
	protected function handleSendChangePuffleRoom($socket) {
		$penguin = $this->penguins[$socket];
		$puffleId = Packet::$Data[2];
		$roomType = Packet::$Data[3];
		
		if($roomType == "igloo" || $roomType == "backyard") {
			if($penguin->database->ownsPuffle($puffleId, $penguin->id)) {
				$toBackyard = intval($roomType == "backyard");
				$penguin->database->sendChangePuffleRoom($puffleId, $toBackyard);
				$penguin->send("%xt%puffleswap%{$penguin->room->internal_id}%$puffleId%$roomType%");				
			}
		}
	}
	
	protected function handlePuffleTrick($socket) {
		$penguin = $this->penguins[$socket];
		$puffle_trick = Packet::$Data[2];
		
		if(is_numeric($puffle_trick)) {
			$penguin->room->send("%xt%puffletrick%{$penguin->room->internal_id}%{$penguin->id}%$puffle_trick%");
		}
	}
	
	protected function handleLoadIsPlayerIglooOpen($socket) {
		$penguin = $this->penguins[$socket];
		$player_id = Packet::$Data[2];
		
		if(isset($this->open_igloos[$player_id])) {
			$open = 1;
		} else {
			$open = 0;
		}
		
		$penguin->send("%xt%pio%{$penguin->room->internal_id}%$open%");
	}
	
	// Add check to make sure puffle belongs to the player
	protected function handleSendPuffleWalk($socket) {
		$penguin = $this->penguins[$socket];
		$puffle_id = Packet::$Data[2];
		$walk_boolean = Packet::$Data[3];
		
		if(is_numeric($puffle_id) && $penguin->database->ownsPuffle($puffle_id, $penguin->id)) {
			if($walk_boolean == 0 || $walk_boolean == 1) {
				$penguin->walkPuffle($puffle_id, $walk_boolean);
			}
			
			if($walk_boolean == 0) {
				$penguin->database->updateColumnById($penguin->id, "Walking", 0);
			} else {
				$penguin->database->updateColumnById($penguin->id, "Walking", $puffle_id);
			}
		}
	}
	
	protected function handlePuffleSwap($socket) {
		$penguin = $this->penguins[$socket];
		$puffle_id = Packet::$Data[2];
		
		if(is_numeric($puffle_id) && $penguin->database->ownsPuffle($puffle_id, $penguin->id)) {
			$puff_info = $penguin->database->getPuffleColumns($puffle_id, array("Type", "Subtype", "Hat"));
			$penguin->room->send("%xt%pufflewalkswap%{$penguin->room->internal_id}%{$penguin->id}%$puffle_id%{$puff_info["Type"]}%{$puff_info["Subtype"]}%1%{$puff_info["Hat"]}%");
			$penguin->database->updateColumnById($penguin->id, "Walking", $puffle_id);
			$penguin->walking_puffle = $penguin->database->getPuffleColumns($puffle_id, array("Type", "Subtype", "Hat") );
			$penguin->walking_puffle = array_values($penguin->walking_puffle);
			array_unshift($penguin->walking_puffle, $puffle_id);
		}
	}
	
	protected function handleGetMyPuffleStats($socket) {
		echo "TODO: Work on handleGetMyPuffleStats\n";
	}
	
	// Check if they exceed puffle limit
	// Also check if types are valid!
	// Implement proper coin deduction
	protected function handleAdoptPuffle($socket) {
		$penguin = $this->penguins[$socket];
		$puffle_type = Packet::$Data[2];
		$puffle_name = ucfirst(Packet::$Data[3]);
		$puffle_subtype = Packet::$Data[4];
		
		// DB stuff
		if(is_numeric($puffle_type) && is_numeric($puffle_subtype)) {
			$puffle_id = $penguin->database->adoptPuffle($penguin->id, $puffle_name, $puffle_type, $puffle_subtype);
			$adoption_date = time();
			$penguin->send("%xt%pn%{$penguin->room->internal_id}%1059%$puffle_id|$puffle_type||$puffle_name|$adoption_date|100|100|100|100|0|0|0|1|%");
			$penguin->database->updateColumnById($penguin->id, "Walking", $puffle_id);
			$penguin->walking_puffle = $penguin->database->getPuffleColumns($puffle_id, array("Type", "Subtype", "Hat") );
			$penguin->walking_puffle = array_values($penguin->walking_puffle);
			array_unshift($penguin->walking_puffle, $puffle_id);
		}
	}
	
	protected function handleCheckPuffleNameWithResponse($socket) {
		$penguin = $this->penguins[$socket];
		$puffle_name = Packet::$Data[2];
		
		$penguin->send("%xt%checkpufflename%{$penguin->room->internal_id}%$puffle_name%1%");
	}
	
	protected function handleCanLikeIgloo($socket) {
		$penguin = $this->penguins[$socket];
		$player_id = Packet::$Data[1];
		
		if($penguin->database->playerIdExists($player_id)) {
			$active_igloo = $penguin->database->getColumnById($player_id, "Igloo");
			$likes = $penguin->database->getIglooLikes($active_igloo);
			
			foreach($likes as $like) {
				if($like["id"] == $penguin->swid) {
					$like_time = $like["time"];
					
					if($like_time < strtotime("-1 day")) {
						$can_like = array(
							"canLike" => true,
							"periodicity" => "ScheduleDaily",
							"nextLike_msecs" => 0
						);
					} else {
						$time_remaining = (time() - $like_time) * 1000;
						
						$can_like = array(
							"canLike" => false,
							"periodicity" => "ScheduleDaily",
							"nextLike_msecs" => $time_remaining
						);
					}
					
					$can_like = json_encode($can_like);
					$penguin->send("%xt%cli%{$penguin->room->internal_id}%$active_igloo%200%$can_like%");
					
					break;
				}
			}
		}
	}
	
	protected function handlePlayerBySwidUsername($socket) {
		$penguin = $this->penguins[$socket];
		$swid_list = Packet::$Data[2];
		
		$username_list = $penguin->database->getUsernamesBySwid($swid_list);
		$penguin->send("%xt%pbsu%{$penguin->room->internal_id}%$username_list%");
	}
	
	protected function handleLikeIgloo($socket) {
		$penguin = $this->penguins[$socket];
		$player_id = Packet::$Data[1];
		
		include "Misc/array_column.php";
		
		if($penguin->database->playerIdExists($player_id)) {
			$active_igloo = $penguin->database->getColumnById($player_id, "Igloo");
			$igloo_likes = $penguin->database->getIglooLikes($active_igloo);
			$swids = array_column($igloo_likes, "id");
			
			if(in_array($penguin->swid, $swids)) {
				foreach($igloo_likes as $like_index => $like) {
					if($like["id"] == $penguin->swid) {
						$like["count"] == ++$like["count"];
						$like["time"] = time();
						$igloo_likes[$like_index] = $like;
						
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
				
				array_push($igloo_likes, $like);
			}
			
			$igloo_likes_json = json_encode($igloo_likes);
			$penguin->database->updateIglooColumn($active_igloo, "Likes", $igloo_likes_json);
		}
	}
	
	protected function handleGetIglooLikeBy($socket) {
		$penguin = $this->penguins[$socket];
		$player_id = Packet::$Data[1];
		
		if($penguin->database->playerIdExists($player_id)) {
			$igloo_id = $penguin->database->getColumnById($player_id, "Igloo");
			$igloo_likes = $penguin->database->getIglooLikes($igloo_id);
			$total_likes = $penguin->database->getTotalIglooLikes($player_id);
			
			$likes = array(
				"likedby" => array(
					"counts" => array(
						"count" => $total_likes,
						"maxCount" => $total_likes,
						"accumCount" => $total_likes
					),
					"IDs" => $igloo_likes
				)
			);
			
			$likes_json = json_encode($likes);
			$penguin->send("%xt%gili%{$penguin->room->internal_id}%{$igloo_id}%200%$likes_json%");
		}
	}
	
	protected function handleGetOpenIglooList($socket) {
		$penguin = $this->penguins[$socket];
		$total_likes = $penguin->database->getTotalIglooLikes($penguin->id);
		
		$open_igloos = implode('%', array_map(
			function($player_id, $username) use ($penguin, $total_likes) {
				if($player_id == $penguin->id) {
					$likes = $total_likes;
				} else {
					$likes = $penguin->database->getTotalIglooLikes($player_id);
				}
			
				return $player_id . '|' . $username . '|' . $likes . '|0|0';
			}, array_keys($this->open_igloos), $this->open_igloos));
		
		$penguin->send("%xt%gr%{$penguin->room->internal_id}%$total_likes%0%$open_igloos%");
	}
	
	protected function handleGetBestFriendsList($socket) {
		$penguin = $this->penguins[$socket];
		$penguin->send("%xt%gbffl%{$penguin->room->internal_id}%");
	}
	
	// People can steal other people's igloos this way.. need to add checks to make sure they are the owners!
	protected function handleUpdateIglooSlotSummary($socket) {
		$penguin = $this->penguins[$socket];
		$active_igloo = Packet::$Data[2];
		
		if(is_numeric($active_igloo) && $penguin->database->iglooExists($active_igloo)) {
			$penguin->active_igloo = $active_igloo;
			$penguin->database->updateColumnById($penguin->id, "Igloo", $active_igloo);
			
			$raw_slot_summary = Packet::$Data[3];
			$slot_summary = explode(',', $raw_slot_summary);
			
			foreach($slot_summary as $summary) {
				list($igloo_id, $locked) = explode('|', $summary);
				if(is_numeric($igloo_id) && is_numeric($locked)) {
					if($penguin->database->iglooExists($igloo_id)) {
						$penguin->database->updateIglooColumn($igloo_id, "Locked", $locked);
						
						if($locked == 0 && $penguin->active_igloo == $igloo_id) {
							$this->open_igloos[$penguin->id] = $penguin->username;
						} elseif($locked == 1 && $penguin->active_igloo == $igloo_id) {
							unset($this->open_igloos[$penguin->id]);
						}
					}
				}
			}
			
			$igloo_details = $penguin->database->getIglooDetails($active_igloo);
			$penguin->room->send("%xt%uvi%{$penguin->room->internal_id}%$active_igloo%$igloo_details%");
		}
	}
	
	protected function handlePlayerThrowBall($socket) {
		$penguin = $this->penguins[$socket];
		
		$x = Packet::$Data[2];
		$y = Packet::$Data[3];
		
		$penguin->room->send("%xt%sb%{$penguin->room->internal_id}%{$penguin->id}%$x%$y%");
	}
	
	protected function handleSendEmote($socket) {
		$penguin = $this->penguins[$socket];
		$emote_id = Packet::$Data[2];
		
		$penguin->room->send("%xt%se%{$penguin->room->internal_id}%{$penguin->id}%$emote_id%");
	}
	
	protected function handleSendMessage($socket) {
		$penguin = $this->penguins[$socket];
		$message = Packet::$Data[3];

		$penguin->room->send("%xt%sm%{$penguin->room->internal_id}%{$penguin->id}%$message%");
	}
	
	protected function handleAddIglooLayout($socket) {
		$penguin = $this->penguins[$socket];
		
		$owned_igloo_count = $penguin->database->getOwnedIglooCount($penguin->id);
		
		if($owned_igloo_count < 3) {
			$igloo_id = $penguin->database->addIglooLayout($penguin->id);
			$penguin->active_igloo = $igloo_id;
			$igloo_details = $penguin->database->getIglooDetails($igloo_id, ++$owned_igloo_count);
			$penguin->send("%xt%al%{$penguin->room->internal_id}%{$penguin->id}%$igloo_details%");
		}
		
	}
	
	protected function handleSendBuyIglooType($socket) {
		$penguin = $this->penguins[$socket];
		$igloo_id = Packet::$Data[2];
		
		if(!isset($this->igloos[$igloo_id])) {
			return $penguin->send("%xt%e%-1%402%");
		} elseif(isset($penguin->igloos[$igloo_id])) { // May not be right lol?
			return $penguin->send("%xt%e%-1%400%");
		}
		
		$cost = $this->igloos[$igloo_id];
		if($penguin->coins < $cost) {
			return $penguin->send("%xt%e%-1%401%");
		} else {
			$penguin->buyIgloo($igloo_id, $cost);
		}
	}
	
	protected function handleSendBuyIglooFloor($socket) {
		$penguin = $this->penguins[$socket];
		$floor_id = Packet::$Data[2];
		
		if(!isset($this->floors[$floor_id])) {
			return $penguin->send("%xt%e%-1%402%");
		} elseif(isset($penguin->floors[$floor_id])) {
			return $penguin->send("%xt%e%-1%400%");
		}
		
		$cost = $this->floors[$floor_id];
		if($penguin->coins < $cost) {
			return $penguin->send("%xt%e%-1%401%");
		} else {
			$penguin->buyFloor($floor_id, $cost);
		}
		
	}
		
	protected function handleBuyFurniture($socket) {
		$penguin = $this->penguins[$socket];
		$furniture_id = Packet::$Data[2];
		
		if(!isset($this->furniture[$furniture_id])) {
			return $penguin->send("%xt%e%-1%402%");
		}
		
		$cost = $this->furniture[$furniture_id];
		if($penguin->coins < $cost) {
			return $penguin->send("%xt%e%-1%401%");
		} else {
			$penguin->buyFurniture($furniture_id, $cost);
		}
	}
	
	// Need to add checks to make sure they are the owner!
	protected function handleUpdateIglooConfiguration($socket) {
		$penguin = $this->penguins[$socket];
		
		$active_igloo = Packet::$Data[2];
		if(is_numeric($active_igloo) && $penguin->database->iglooExists($active_igloo)) {
			$igloo_type = Packet::$Data[3];
			$floor = Packet::$Data[4];
			$location = Packet::$Data[5];
			$music = Packet::$Data[6];
			$furniture = Packet::$Data[7];
			
			if(is_numeric($igloo_type) && is_numeric($floor) && is_numeric($location) && is_numeric($music) && (strstr($furniture, ',') !== false)) {
				$penguin->active_igloo = $active_igloo;
				$penguin->database->updateColumnById($penguin->id, "Igloo", $penguin->active_igloo);
				$penguin->database->updateIglooColumn($penguin->active_igloo, "Type", $igloo_type);
				$penguin->database->updateIglooColumn($penguin->active_igloo, "Floor", $floor);
				$penguin->database->updateIglooColumn($penguin->active_igloo, "Location", $location);
				$penguin->database->updateIglooColumn($penguin->active_igloo, "Music", $music);
				$penguin->database->updateIglooColumn($penguin->active_igloo, "Furniture", $furniture);
				
				$penguin->send("%xt%uic%{$penguin->room->internal_id}%{$penguin->id}%{$penguin->active_igloo}%$igloo_type:$floor:$location:$music:$furniture%");
				
				$igloo_details = $penguin->database->getIglooDetails($active_igloo);
				$penguin->room->send("%xt%uvi%{$penguin->room->internal_id}%$active_igloo%$igloo_details%");
			}
		}
	}
	
	// Should use $penguin->id instead of Packet::$Data[2].. ?
	protected function handleGetAllIglooLayouts($socket) {
		$penguin = $this->penguins[$socket];
		$player_id = Packet::$Data[2];
		
		if($penguin->database->playerIdExists($player_id)) {
			$igloo_layouts = $penguin->database->getAllIglooLayouts($player_id);
			$active_igloo = $penguin->database->getColumnById($player_id, "Igloo");
			$total_likes = $penguin->database->getTotalIglooLikes($player_id);
			
			$penguin->send("%xt%gail%{$penguin->room->internal_id}%$player_id%$active_igloo%$igloo_layouts%");
			$penguin->send("%xt%gaili%{$penguin->room->internal_id}%$total_likes%%");
			
		}
	}
	
	protected function handleBuyIglooLocation($socket) {
		$penguin = $this->penguins[$socket];
		$location_id = Packet::$Data[2];
		
		if(!isset($this->locations[$location_id])) {
			return $penguin->send("%xt%e%-1%402%");
		} elseif(isset($penguin->locations[$location_id])) {
			return $penguin->send("%xt%e%-1%400%");
		}
		
		$cost = $this->locations[$location_id];
		if($penguin->coins < $cost) {
			return $penguin->send("%xt%e%-1%401%");
		} else {
			$penguin->buyLocation($location_id, $cost);
		}
	}
	
	protected function handleJoinPlayerRoom($socket) {
		$penguin = $this->penguins[$socket];
		$player_id = Packet::$Data[2];
		$room_type = Packet::$Data[3];
		
		if($penguin->database->playerIdExists($player_id)) {
			$external_id = $player_id + 1000;
			
			if(!isset($this->rooms[$external_id])) {
				$this->rooms[$external_id] = new Room($external_id, $player_id);
			}
			
			$penguin->send("%xt%jp%$player_id%$player_id%$external_id%$room_type%");
			$this->joinRoom($penguin, $external_id);
		}
	}
	
	protected function handleGetPufflesByPlayerId($socket) {
		$penguin = $this->penguins[$socket];
		$player_id = Packet::$Data[2];
		$room_type = Packet::$Data[3];
		
		if($penguin->database->playerIdExists($player_id)) {
			$puffles = $penguin->database->getPuffles($player_id, $room_type);
			
			// Doesn't necessarily have to be $player_id, I think?
			$penguin->send("%xt%pg%{$penguin->room->internal_id}%$player_id%$puffles%$room_type%");
		}
	}
	
	protected function handleGetGameData($socket) {
		$penguin = $this->penguins[$socket];
		$penguin->send("%xt%ggd%{$penguin->room->internal_id}%Kitsune%");
	}
	
	protected function handleGetActiveIgloo($socket) {
		$penguin = $this->penguins[$socket];
		$player_id = Packet::$Data[2];
		
		if($penguin->database->playerIdExists($player_id)) {
			$active_igloo = $penguin->database->getColumnById($player_id, "Igloo");
			
			if($player_id == $penguin->id) {
				$penguin->active_igloo = $active_igloo;
			}
			
			$igloo_details = $penguin->database->getIglooDetails($active_igloo);
			$penguin->send("%xt%gm%{$penguin->room->internal_id}%$player_id%$igloo_details%");
		}
	}
	
	protected function handleGetFurnitureInventory($socket) {
		$penguin = $this->penguins[$socket];
		
		$furniture_list = $penguin->database->getColumnsById($penguin->id, array("Furniture", "Floors", "Igloos", "Locations"));
		
		$furniture_array = explode(',', $furniture_list["Furniture"]);
		foreach($furniture_array as $furniture) {
			$furniture_details = explode('|', $furniture);
			list($furniture_id, $purchase_date, $quantity) = $furniture_details;
			$penguin->furniture[$furniture_id] = $quantity;
		}
		
		$flooring_array = explode(',', $furniture_list["Floors"]);
		foreach($flooring_array as $flooring) {
			$flooring_details = explode('|', $flooring);
			list($flooring_id, $purchase_date) = $flooring_details;
			$penguin->floors[$flooring_id] = $purchase_date;
		}
		
		$igloos_array = explode(',', $furniture_list["Igloos"]);
		foreach($igloos_array as $igloo) {
			$igloo_details = explode('|', $igloo);
			list($igloo_type, $purchase_date) = $igloo_details;
			$penguin->igloos[$igloo_type] = $purchase_date;
		}
		
		$location_array = explode(',', $furniture_list["Locations"]);
		foreach($location_array as $location) {
			$location_details = explode('|', $location);
			list($location_id, $purchase_date) = $location_details;
			$penguin->locations[$location_id] = $purchase_date;
		}
		
		$furniture_list = implode('%', $furniture_list);
		$penguin->send("%xt%gii%{$penguin->room->internal_id}%$furniture_list%");
	}
	
	// Because I'm super lazy
	protected function handleSendUpdatePlayerClothing($socket) {
		$penguin = $this->penguins[$socket];
		$item_id = Packet::$Data[2];
		$clothing_type = substr(Packet::$Data[0], 2);
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
			
		call_user_func(array($penguin, "update{$clothing[$clothing_type]}"), $item_id);
	}
	
	protected function handleBuyInventory($socket) {
		$penguin = $this->penguins[$socket];
		$item_id = Packet::$Data[2];
		
		if(!isset($this->items[$item_id])) {
			return $penguin->send("%xt%e%-1%402%");
		} elseif(isset($this->penguin->inventory[$item_id])) {
			return $penguin->send("%xt%e%-1%400%");
		}
		
		$cost = $this->items[$item_id];
		if($penguin->coins < $cost) {
			return $penguin->send("%xt%e%-1%401%");
		} else {
			$penguin->addItem($item_id, $cost);
		}
	}
	
	protected function handleUpdatePlayerAction($socket) {
		$penguin = $this->penguins[$socket];
		$action_id = Packet::$Data[2];
		
		$penguin->room->send("%xt%sa%{$penguin->room->internal_id}%{$penguin->id}%{$action_id}%");
	}
	
	protected function handleSendHeartbeat($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->send("%xt%h%{$penguin->room->internal_id}%");
	}
	
	protected function handleSendPlayerFrame($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->frame = Packet::$Data[2];
		$penguin->room->send("%xt%sf%{$penguin->room->internal_id}%{$penguin->id}%{$penguin->frame}%");
	}
		
	protected function handleSendPlayerMove($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->x = Packet::$Data[2];
		$penguin->y = Packet::$Data[3];
		$penguin->room->send("%xt%sp%{$penguin->room->internal_id}%{$penguin->id}%{$penguin->x}%{$penguin->y}%"); 
	}
	
	protected function handleGetPlayerInfoById($socket) {
		$penguin = $this->penguins[$socket];
		$penguinId = Packet::$Data[2];
		
		if($penguin->database->playerIdExists($penguinId)) {
			$player_array = $penguin->database->getColumnsById($penguinId, array("Username", "SWID"));
			$penguin->send("%xt%pbi%{$penguin->room->internal_id}%{$player_array["SWID"]}%$penguinId%{$player_array["Username"]}%");
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
		
		$inventory_list = implode('%', $penguin->inventory);
		$penguin->send("%xt%gi%-1%$inventory_list%");
	}
	
	public function joinRoom($penguin, $room_id, $x = 0, $y = 0) {
		if(!isset($this->rooms[$room_id])) {
			return;
		} elseif(isset($penguin->room)) {
			$penguin->room->remove($penguin);
		}
		
		$penguin->frame = 1;
		$penguin->x = $x;
		$penguin->y = $y;
		$this->rooms[$room_id]->add($penguin);
	}
	
	private function getOpenRoom() {
		// Non-game/party rooms, perhaps have a totally separate array consisting of these room ids?
		$room_ids = array_keys(
			array_filter($this->rooms, function($room) {
				if($room->external_id <= 810) {
					return true;
				}
			})
		);
		
		shuffle($room_ids);
		foreach($room_ids as $room_id) {
			if(sizeof($this->rooms[$room_id]->penguins) < 75) {
				return $room_id;
			}
		}
		
		return 100;
	}
	
	protected function handleJoinWorld($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->id != Packet::$Data[2]) {
			return $this->removePenguin($penguin);
		}
		
		$login_key = Packet::$Data[3];
		$db_login_key = $penguin->database->getColumnById($penguin->id, "LoginKey");
		
		if($db_login_key != $login_key) {
			$penguin->send("%xt%e%-1%101%");
			$penguin->database->updateColumnByid($penguin->id, "LoginKey", "");
			return $this->removePenguin($penguin);
		}
		
		$penguin->database->updateColumnByid($penguin->id, "LoginKey", "");
		
		$penguin->loadPlayer();
		$penguin->send("%xt%activefeatures%-1%");
		$penguin->send("%xt%js%-1%1%0%0%1%");
		$penguin->send("%xt%gps%-1%{$penguin->id}%");
		
		$puffles = $penguin->database->getPlayerPuffles($penguin->id);
		$penguin->send("%xt%pgu%-1%$puffles%");
		
		$player_string = $penguin->getPlayerString();
		$current_time = time();
		
		$load_player = "$player_string|%{$penguin->coins}%0%1440%$current_time%{$penguin->age}%0%7521%%7%1%0%211843";
		$penguin->send("%xt%lp%-1%$load_player%");
		
		$open_room = $this->getOpenRoom();
		$this->joinRoom($penguin, $open_room, 0, 0);
				
		// The 0 after the player id is probably a transformation id, will be looking into a proper implementation
		$penguin->room->send("%xt%spts%-1%{$penguin->id}%0%{$penguin->avatar}%");
		
		$penguin->send("%xt%cberror%-1%Kitsune is a Club Penguin private server program written in PHP by Arthur designed to emulate the AS3 protocol.%Welcome%");
	}

	protected function handleLogin($socket) {
		$penguin = $this->penguins[$socket];
		$raw_player_string = Packet::$Data['body']['login']['nick'];
		$player_hashes = Packet::$Data['body']['login']['pword'];
		
		$player_array = explode('|', $raw_player_string);
		list($id, $swid, $username) = $player_array;
		
		if($penguin->database->playerIdExists($id) === false) {
			return $this->removePenguin($penguin);
		}
		
		if($penguin->database->usernameExists($username) === false) {
			$penguin->send("%xt%e%-1%101%");
			return $this->removePenguin($penguin);
		}
		
		$hashes_array = explode('#', $player_hashes);
		list($login_key, $confirmation_hash) = $hashes_array;
		
		$db_confirmation_hash = $penguin->database->getColumnById($id, "ConfirmationHash");
		if($db_confirmation_hash != $confirmation_hash) {
			$penguin->send("%xt%e%-1%101%");
			return $this->removePenguin($penguin);
		} else {
			echo "Login successful!\n";
			$penguin->database->updateColumnByid($id, "ConfirmationHash", ""); // Maybe the column should be cleared even when the login is unsuccessful
			$penguin->id = $id;
			$penguin->swid = $swid;
			$penguin->username = $username;
			$penguin->identified = true;
			$penguin->send("%xt%l%-1%");
		}
		
	}
	
	protected function handleDisconnect($socket) {
		$penguin = $this->penguins[$socket];
		
		if(isset($penguin->room)) {
			$penguin->room->remove($penguin);
		}
		
		unset($this->penguins[$socket]);
		
		echo "Player disconnected\n";
	}
	
}

?>