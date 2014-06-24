<?php

namespace Kitsune\ClubPenguin\Plugins\Base;

interface IPlugin {

	public function handleXmlPacket($penguin);
	public function handleWorldPacket($penguin);
	
}

?>