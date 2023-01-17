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

use pocketmine\item\Item;
use pocketmine\utils\Utils;
use pocketmine\world\loot\pool\LootPool;

class LootTable{

	/**
	 * @param LootPool[] $pools
	 */
	public function __construct(private array $pools) {
		Utils::validateArrayValueType($pools, function(LootPool $_) : void{});
	}

	/**
	 * @return LootPool[]
	 */
	public function getPools() : array{
		return $this->pools;
	}

	/**
	 * @return Item[]
	 */
	public function generate(LootContext $context) : array{
		$items = [];
		foreach($this->pools as $pool){
			if($pool->evaluateConditions($context)){
				foreach($pool->generate($context) as $item) {
					$items[] = $item;
				}
			}
		}

		return $items;
	}
}