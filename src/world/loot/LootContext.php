<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\world\loot;

use pocketmine\player\Player;
use pocketmine\utils\Random;
use pocketmine\world\World;

class LootContext{

	private World $world;

	private mixed $origin;

	private ?Player $player;

	private Random $random;

	public function __construct(World $world, mixed $origin, ?Player $player = null, Random $random = null) {
		$this->world = $world;
		$this->origin = $origin;
		$this->player = $player;
		$this->random  = $random ?? new Random();
	}

	public function getWorld() : World{
		return $this->world;
	}

	/**
	 * Returns what caused the table to be generated.
	 * It could be:
	 * - a player when fishing
	 * - an entity at death
	 * - a chest from a structure
	 */
	public function getOrigin() : mixed{
		return $this->origin;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getRandom() : Random{
		return $this->random;
	}
}
