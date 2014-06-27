<?php

namespace Kitsune\ClubPenguin\Handlers\Game;

use Kitsune\ClubPenguin\Packets\Packet;

trait SledRacing {

	protected function handleSendMove($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->waddleRoom !== null) {
			array_shift(Packet::$Data);
			
			$penguin->room->send("%xt%zm%" . implode('%', Packet::$Data) . '%');
		}
	}
	
}

?>