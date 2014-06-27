<?php

namespace Kitsune\ClubPenguin\Handlers\Game;

use Kitsune\ClubPenguin\Packets\Packet;

trait Rink {

	public $rinkPuck = array(0, 0, 0, 0);

	protected function handleGameMove($socket) {
		$penguin = $this->penguins[$socket];
		
		$this->rinkPuck = array_splice(Packet::$Data, 3);
		
		$puckData = implode('%', $this->rinkPuck);
		
		$penguin->send("%xt%zm%{$penguin->room->internalId}%{$penguin->id}%$puckData%");
	}
	
	protected function handleGetGame($socket) {
		$penguin = $this->penguins[$socket];
		
		$puckData = implode('%', $this->rinkPuck);
		
		$penguin->send("%xt%gz%{$penguin->room->internalId}%$puckData%");
	}
	
}

?>
