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
			"i#qpp" => "handleGetPlayerPins",
			"i#qpa" => "handleGetPlayerAwards",
			
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
			"u#ss" => "handleSafeMessage",
			
			"l#mg" => "handleGetMail",
			"l#mst" => "handleStartMailEngine",
			"l#ms" => "handleSendMailItem",
			"l#mc" => "handleMailChecked",
			"l#md" => "handleDeleteMailItem",
			"l#mdp" => "handleDeleteMailFromUser",
			
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
			"o#initban" => "handleInitBan",
			"o#ban" => "handleModeratorBan",
			"o#moderatormessage" => "handleModeratorMessage",
			
			"st#sse" => "handleStampAdd",
			"st#gps" => "handleGetStamps",
			"st#gmres" => "handleGetRecentStamps",
			"st#gsbcd" => "handleGetBookCover",
			"st#ssbcd" => "handleUpdateBookCover",
			
			"p#pg" => "handleGetPufflesByPlayerId",
			"p#checkpufflename" => "handleCheckPuffleNameWithResponse",
			"p#pn" => "handleAdoptPuffle",
			"p#pgmps" => "handleGetMyPuffleStats",
			"p#pw" => "handleSendPuffleWalk",
			"p#pufflewalkswap" => "handlePuffleSwap",
			"p#puffletrick" => "handlePuffleTrick",
			"p#puffleswap" => "handleSendChangePuffleRoom",
			"p#pgpi" => "handleGetPuffleCareInventory",
			"p#papi" => "handleSendBuyPuffleCareItem",
			"p#phg" => "handleGetPuffleHanderStatus",
			"p#puphi" => "handleVisitorHatUpdate",
			"p#pp" => "handleSendPufflePlay",
			
			"t#at" => "handleOpenPlayerBook",
			"t#rt" => "handleClosePlayerBook"
		),
		
		"z" => array(
			"gz" => "handleGetGame",
			"m" => "handleGameMove"
		)
	);

	public $rooms = array();
	public $items = array();
	public $pins = array();
	public $locations = array();
	public $furniture = array();
	public $floors = array();
	public $igloos = array();
	
	public $spawnRooms = array();
	public $penguinsById = array();
	
	private $openIgloos = array();
	
	public $rinkPuck = array(0, 0, 0, 0);
	
	public function __construct() {
		parent::__construct();
		
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
		
		$agentRooms = array(210, 212, 323, 803);
		$rockhoppersShip = array(422, 423);
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
		foreach($items as $itemIndex => $item) {
			$itemId = $item["paper_item_id"];
			
			$this->items[$itemId] = $item["cost"];
			
			if($item["type"] == 8) {
				array_push($this->pins, $itemId);
			}
			
			unset($items[$itemIndex]);
		}
		
		$locations = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/igloo_locations.json");
		foreach($locations as $locationIndex => $location) {
			$locationId = $location["igloo_location_id"];
			$this->locations[$locationId] = $location["cost"];
			
			unset($locations[$locationIndex]);
		}
		
		$furnitureList = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/furniture_items.json");
		foreach($furnitureList as $furnitureIndex => $furniture) {
			$furnitureId = $furniture["furniture_item_id"];
			$this->furniture[$furnitureId] = $furniture["cost"];
			
			unset($furnitureList[$furnitureIndex]);
		}
		
		$floors = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/igloo_floors.json");
		foreach($floors as $floorIndex => $floor) {
			$floorId = $floor["igloo_floor_id"];
			$this->floors[$floorId] = $floor["cost"];
			
			unset($floors[$floorIndex]);
		}
		
		$igloos = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/igloos.json");
		foreach($igloos as $iglooId => $igloo) {
			$this->igloos[$iglooId] = $igloo["cost"];
			
			unset($igloos[$iglooId]);
		}
		
		$careItems = $downloadAndDecode("http://media1.clubpenguin.com/play/en/web_service/game_configs/puffle_items.json");
		foreach($careItems as $careId => $careItem) {
			$itemId = $careItem["puffle_item_id"];
			
			$this->careItems[$itemId] = $careItem["cost"];
			
			unset($careItems[$careId]);
		}
		
		Logger::Fine("World server is online");
	}
	
	protected function handleGameMove($socket) {
		$penguin = $this->penguins[$socket];
		
		$this->rinkPuck = array_splice(Packet::$Data, 3);
		$puckData = implode('%', $this->rinkPuck);
		
		$penguin->send("%xt%zm%{$penguin->room->internalId}%{$penguin->id}%$puckData%");
	}
	
	protected function handleGetGame($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->send("%xt%gz%{$penguin->room->internalId}%" . implode('%', $this->rinkPuck) . "%");
	}
	
	protected function handleSendPufflePlay($socket) {
		Logger::Warn("Need to log packets");
	}
	
	protected function handleVisitorHatUpdate($socket) {
		$penguin = $this->penguins[$socket];
		
		$puffleId = Packet::$Data[2];
		$hatId = Packet::$Data[3];

		if($penguin->database->ownsPuffle($puffleId, $penguin->id) && isset($this->careItems[$hatId])) {
			$penguin->database->updatePuffleColumn($puffleId, "Hat", $hatId);
			
			$penguin->room->send("%xt%puphi%{$penguin->room->internalId}%$puffleId%$hatId%");
		}
	}
	
	protected function handleGetPuffleHanderStatus($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->send("%xt%phg%{$penguin->room->internalId}%1%");
	}
	
	protected function handleSendBuyPuffleCareItem($socket) {
		$penguin = $this->penguins[$socket];
		
		$itemId = Packet::$Data[2];
		
		if(!isset($this->careItems[$itemId])) {
			$penguin->send("%xt%e%-1%402%");
		} else {
			$itemCost = $this->careItems[$itemId];
			
			if($penguin->coins < $itemCost) {
				$penguin->send("%xt%e%-1%401%");
			} else {
				$penguin->buyPuffleCareItem($itemId, $itemCost);
			}
		}
	}
	
	protected function handleGetPuffleCareInventory($socket) {
		$penguin = $this->penguins[$socket];
		
		$careInventory = "";
		
		if(!empty($penguin->careInventory)) {
			$careInventory = implode('%', array_map(
				function($itemId, $quantity) {
					return sprintf("%d|%d", $itemId, $quantity);
				}, array_keys($penguin->careInventory), $penguin->careInventory
			));
		}
		
		$penguin->send("%xt%pgpi%{$penguin->room->internalId}%$careInventory%");
	}
	
	// Maybe add spam-filter-thing for this?
	protected function handleClosePlayerBook($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->room->send("%xt%rt%{$penguin->room->internalId}%{$penguin->id}%");
	}
	
	protected function handleOpenPlayerBook($socket) {
		$penguin = $this->penguins[$socket];
		$toyId = Packet::$Data[2];
		
		if(is_numeric($toyId) && is_numeric(Packet::$Data[3])) {
			$penguin->room->send("%xt%at%{$penguin->room->internalId}%{$penguin->id}%$toyId%1%");
		}
	}
	
	protected function handleDeleteMailFromUser($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguinId = Packet::$Data[2];
		
		if($penguin->database->playerIdExists($penguinId)) {
			$penguin->database->deleteMailFromUser($penguin->id, $penguinId);
			$postcardCount = $penguin->database->getPostcardCount($penguin->id);
			
			$penguin->send("%xt%mdp%{$penguin->room->internalId}%$postcardCount%");
		}
	}
	
	protected function handleDeleteMailItem($socket) {
		$penguin = $this->penguins[$socket];
		
		$postcardId = Packet::$Data[2];
		
		if(is_numeric($postcardId) && $penguin->database->ownsPostcard($postcardId, $penguin->id)) {
			$penguin->database->deleteMail($postcardId);
		}
	}
	
	protected function handleMailChecked($socket) {
		$penguin = $this->penguins[$socket];
		
		$penguin->database->mailChecked($penguin->id);
	}
	
	protected function handleSendMailItem($socket) {
		$penguin = $this->penguins[$socket];
		
		$recipientId = Packet::$Data[2];
		$postcardType = Packet::$Data[3];
		
		if($penguin->database->playerIdExists($recipientId) && is_numeric($postcardType)) {
			if($penguin->coins < 10) {
				$penguin->send("%xt%ms%{$penguin->room->internalId}%{$penguin->coins}%2%");
			} else {
				$postcardCount = $penguin->database->getPostcardCount($recipientId);
				if($postcardCount == 100) {
					$penguin->send("%xt%ms%{$penguin->room->internalId}%{$penguin->coins}%0%");
				} else {
					$penguin->setCoins($penguin->coins - 10);
					
					$sentDate = time();
					$postcardId = $penguin->database->sendMail($recipientId, $penguin->username, $penguin->id, "", $sentDate, $postcardType);
					$penguin->send("%xt%ms%{$penguin->room->internalId}%{$penguin->coins}%1%");
					
					if(isset($this->penguinsById[$recipientId])) {
						$this->penguinsById[$recipientId]->send("%xt%mr%-1%{$penguin->username}%{$penguin->id}%$postcardType%%$sentDate%$postcardId%");
					}
				}
			}
		}
	}
	
	protected function handleSafeMessage($socket) {
		$penguin = $this->penguins[$socket];
		$messageId = Packet::$Data[2];
		
		if(is_numeric($messageId)) {
			$penguin->room->send("%xt%ss%{$penguin->room->internalId}%{$penguin->id}%$messageId%");
		}
	}
	
	protected function handleGetPlayerPins($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[2];
		$pins = "";
		if(is_numeric($playerId)){
			$inventory = explode('%', $penguin->database->getColumnById($playerId, "Inventory"));
			foreach($this->pins as $pin){
				if(in_array($pin, $inventory)){
					$pins .= "$pin|".time()."|0%";
				}
			}
			$pins = rtrim($pins, "%");
			$penguin->send("%xt%qpp%-1%$pins%");
		}
	}
	
	protected function handleGetPlayerAwards($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[2];
		$penguin->send("%xt%qpa%-1%$playerId%%");
	}
	
	protected function handleStampAdd($socket) {
		$penguin = $this->penguins[$socket];
		$stampId = Packet::$Data[2];
		if(is_numeric($stampId)){
			$stamps = $penguin->database->getColumnById($penguin->id, "Stamps");
			if(strpos($stamps, $stampId.",") === false) {
				$penguin->database->updateColumnById($penguin->id, "Stamps", $stamps . $stampId . ",");
				$penguin->send("%xt%sse%-1%$stampId%{$penguin->coins}%");
			}
		}
	}
	
	protected function handleGetStamps($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[2];
		if(is_numeric($playerId)) {
			$stamps = rtrim(str_replace(",", "|", $penguin->database->getColumnById($playerId, "Stamps")), "|");
			$penguin->send("%xt%gps%-1%$playerId%$stamps%");
		}
	}
	
	protected function handleGetRecentStamps($socket) {
		$penguin = $this->penguins[$socket];
		$penguin->send("%xt%gmres%-1%%");
	}
	
	protected function handleGetBookCover($socket) {
		$penguin = $this->penguins[$socket];
		$penguinId = Packet::$Data[2];
		if(is_numeric($penguinId)) {
			$stampBook = $penguin->database->getColumnById($penguinId, "StampBook");
			$penguin->send("%xt%gsbcd%-1%$stampBook%");
		}
	}
	
	protected function handleUpdateBookCover($socket) {
		$penguin = $this->penguins[$socket];
		if(is_numeric(Packet::$Data[2].Packet::$Data[3].Packet::$Data[4].Packet::$Data[5])) {
			$newCover = Packet::$Data[2]."%".Packet::$Data[3]."%".Packet::$Data[4]."%".Packet::$Data[5];
			if(count(Packet::$Data) > 5){
				foreach(range(6, 12) as $num){
					if(isset(Packet::$Data[$num])){
						$newCover .= "%" . Packet::$Data[$num];
					}
				}
			}
			$penguin->database->updateColumnById($penguin->id, "StampBook", $newCover);
		}
		$penguin->send("%xt%ssbcd%-1%");
	}
	
	public function mutePlayer($targetPlayer, $moderatorUsername) {
		if(!$targetPlayer->muted) {
			$targetPlayer->muted = true;
			$targetPlayer->send("%xt%moderatormessage%-1%2%");
			Logger::Info("$moderatorUsername has muted {$targetPlayer->username}");
		} else {
			$targetPlayer->muted = false;
			Logger::Info("$moderatorUsername has unmuted {$targetPlayer->username}");
		}
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
	
	protected function handleInitBan($socket) {
		$penguin = $this->penguins[$socket];

		if($penguin->moderator) {
			$playerId = Packet::$Data[2];
			$phrase = Packet::$Data[3];

			if(is_numeric($playerId)) {
				$targetPlayer = $this->getPlayerById($playerId);
				if($targetPlayer !== null) {
					$penguin->send("%xt%initban%-1%{$playerId}%0%0%{$phrase}%{$targetPlayer->username}%");
				}
			}
		}
	}
	
	protected function handleModeratorBan($socket) {
		$penguin = $this->penguins[$socket];
		$player = Packet::$Data[2];
		$banType = Packet::$Data[3];
		$banReason = Packet::$Data[4];
		$banDuration = Packet::$Data[5];
		$penguinName = Packet::$Data[6];
		$banNotes = Packet::$Data[7];
		if($penguin->moderator) {

			if(is_numeric($player)) {
				$targetPlayer = $this->getPlayerById($player);
				if($targetPlayer !== null) {
					if($banDuration !== 0){
						$targetPlayer->database->updateColumnById($targetPlayer->id, "Banned", strtotime("+".$banDuration." hours"));
					}else{
						$targetPlayer->database->updateColumnById($targetPlayer->id, "Banned", "perm");
					}
					$targetPlayer->send("%xt%ban%-1%$banType%$banReason%$banDuration%$banNotes%");
					$this->removePenguin($targetPlayer);
					Logger::Info("{$penguin->username} has banned {$targetPlayer->username} for $banDuration hours");
				}
			}
		}
	}
	
	protected function handleModeratorMessage($socket) {
		$penguin = $this->penguins[$socket];
		$type = Packet::$Data[1];
		$stype = Packet::$Data[2];
		$player = Packet::$Data[3];
		if($penguin->moderator) {

			if(is_numeric($player)) {
				$targetPlayer = $this->getPlayerById($player);
				if($targetPlayer !== null) {
					$targetPlayer->send("%xt%moderatormessage%-1%$stype%");
				}
			}
		}
	}
	
	public function kickPlayer($targetPlayer, $moderatorUsername) {
		$targetPlayer->send("%xt%moderatormessage%-1%3%");
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
		$penguin = $this->penguins[$socket];
		
		$puffleStats = $penguin->database->getPuffleStats($penguin->id);
		
		$penguin->send("%xt%pgmps%{$penguin->room->internalId}%$puffleStats%");
	}
	
	// Check if they exceed puffle limit (?)
	// Also check if types are valid!
	protected function handleAdoptPuffle($socket) {
		$penguin = $this->penguins[$socket];
		$puffleType = Packet::$Data[2];
		$puffleName = ucfirst(Packet::$Data[3]);
		$puffleSubtype = Packet::$Data[4];
		
		if($puffleSubtype == 0) {
			$puffleCost = 400;
		} else {
			$puffleCost = 800;
		}
		
		if(is_numeric($puffleType) && is_numeric($puffleSubtype)) {
			$puffleId = $penguin->database->adoptPuffle($penguin->id, $puffleName, $puffleType, $puffleSubtype);
			$adoptionDate = time();
			
			if($puffleSubtype == 0) {
				$puffleSubtype = "";
			}
			
			$penguin->buyPuffleCareItem(3, 0, 5, true); // Puffle O's
			$penguin->buyPuffleCareItem(76, 0, 1, true); // Apple
			
			$postcardId = $penguin->database->sendMail($penguin->id, "sys", 0, $puffleName, $adoptionDate, 111);
			$penguin->send("%xt%mr%-1%sys%0%111%$puffleName%$adoptionDate%$postcardId%"); 
			
			$penguin->setCoins($penguin->coins - $puffleCost);
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
			
			if(!empty($likes)) {
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
	
	protected function handleUpdateIglooSlotSummary($socket) {
		$penguin = $this->penguins[$socket];
		$activeIgloo = Packet::$Data[2];
		
		if(is_numeric($activeIgloo) && $penguin->database->ownsIgloo($activeIgloo, $penguin->id)) {
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
			return $penguin->send("%xt%e%-1%500%");
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
	
	protected function handleUpdateIglooConfiguration($socket) {
		$penguin = $this->penguins[$socket];
		
		$activeIgloo = Packet::$Data[2];
		
		if(is_numeric($activeIgloo) && $penguin->database->ownsIgloo($activeIgloo, $penguin->id)) {
			$iglooType = Packet::$Data[3];
			$floor = Packet::$Data[4];
			$location = Packet::$Data[5];
			$music = Packet::$Data[6];
			$furniture = Packet::$Data[7];
			
			if(is_numeric($iglooType) && is_numeric($floor) && is_numeric($location) && is_numeric($music)) {
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
	
	private function joinPuffleData(array $puffleData, $walkingPuffleId = null, $iglooAppend = false) {
		$puffles = implode('%', array_map(
			function($puffle) use($walkingPuffleId, $iglooAppend) {
				if($puffle["ID"] != $walkingPuffleId) {
					if($puffle["Subtype"] == 0) {
						$puffle["Subtype"] = "";
					}
					
					$playerPuffle = implode('|', $puffle);
					
					if($iglooAppend !== false) {
						$playerPuffle .= "|0|0|0|0";
					}
					
					return $playerPuffle;
				}
			}, $puffleData
		));	
		
		return $puffles;
	}
	
	protected function handleGetPufflesByPlayerId($socket) {
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[2];
		$roomType = Packet::$Data[3];
		
		if($penguin->database->playerIdExists($playerId)) {
			$puffleData = $penguin->database->getPuffles($playerId, $roomType);
			$ownedPuffles = sizeof($puffleData);
			
			$walkingPuffle = null;
			if(!empty($penguin->walkingPuffle)) {
				list($walkingPuffle) = $penguin->walkingPuffle;
			}
			
			$playerPuffles = $this->joinPuffleData($puffleData, $walkingPuffle, true);
			
			$penguin->send("%xt%pg%{$penguin->room->internalId}%$ownedPuffles%$playerPuffles%");
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
		
		$furnitureInventory = implode(',', array_map(
			function($furnitureId, $furnitureDetails) {
				list($purchaseDate, $furnitureQuantity) = $furnitureDetails;
				
				return sprintf("%d|%d%|d", $furnitureId, $purchaseDate, $furnitureQuantity);
			}, array_keys($penguin->furniture), $penguin->furniture
		));
		
		$floorInventory = implode(',', array_map(
			function($floorId, $purchaseDate) {
				return sprintf("%d|%d", $floorId, $purchaseDate);
			}, array_keys($penguin->floors), $penguin->floors
		));
		
		$iglooInventory = implode(',', array_map(
			function($iglooType, $purchaseDate) {
				return sprintf("%d|%d", $iglooType, $purchaseDate);
			}, array_keys($penguin->igloos), $penguin->igloos
		));
		
		$locationInventory = implode(',', array_map(
			function($locationId, $purchaseDate) {
				return sprintf("%d|%d", $locationId, $purchaseDate);
			}, array_keys($penguin->locations), $penguin->locations
		));
		
		$penguin->send("%xt%gii%{$penguin->room->internalId}%$furnitureInventory%$floorInventory%$iglooInventory%$locationInventory%");
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
		$penguin = $this->penguins[$socket];
		
		$receivedPostcards = $penguin->database->getPostcardsById($penguin->id);
		$receivedPostcards = array_reverse($receivedPostcards, true);
		
		$penguinPostcards = implode('%', array_map(
			function($postcard) {			
				return implode('|', $postcard);
			}, $receivedPostcards
		));
		
		$penguin->send("%xt%mg%-1%$penguinPostcards%");
	}
	
	protected function handleGetLastRevision($socket) {
		$this->penguins[$socket]->send("%xt%glr%-1%10915%");
	}
	
	protected function handleStartMailEngine($socket) {
		$penguin = $this->penguins[$socket];
		
		$unreadCount = $penguin->database->getUnreadPostcardCount($penguin->id);
		$postcardCount = $penguin->database->getPostcardCount($penguin->id);
		
		$penguin->send("%xt%mst%-1%$unreadCount%$postcardCount%");
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
		
		$this->penguinsById[$penguin->id] = $penguin;
		
		$penguin->send("%xt%activefeatures%-1%");
		
		$isModerator = intval($penguin->moderator);
		$penguin->send("%xt%js%-1%1%0%$isModerator%1%");
		$stamps = rtrim(str_replace(",", "|", $penguin->database->getColumnById($penguin->id, "Stamps")), "|");
		$penguin->send("%xt%gps%-1%{$penguin->id}%$stamps%");
		
		$puffleData = $penguin->database->getPlayerPuffles($penguin->id);
		$puffles = $this->joinPuffleData($puffleData);
		
		$penguin->send("%xt%pgu%-1%$puffles%");
		
		$playerString = $penguin->getPlayerString();
		$loginTime = time(); // ?
		
		$loadPlayer = "$playerString|%{$penguin->coins}%0%1440%$loginTime%{$penguin->age}%0%7521%%7%1%0%211843";
		$penguin->send("%xt%lp%-1%$loadPlayer%");
		
		$openRoom = $this->getOpenRoom();
		$this->joinRoom($penguin, $openRoom, 0, 0);
				
		// The 0 after the player id is probably a transformation id, will be looking into a proper implementation
		$penguin->room->send("%xt%spts%-1%{$penguin->id}%0%{$penguin->avatar}%");
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