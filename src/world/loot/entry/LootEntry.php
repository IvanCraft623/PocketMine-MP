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

namespace pocketmine\world\loot\entry;

use pocketmine\item\Item;
use pocketmine\utils\Utils;
use pocketmine\world\loot\condition\LootCondition;
use pocketmine\world\loot\condition\LootConditionHandlingTrait;
use pocketmine\world\loot\entry\function\EntryFunction;
use pocketmine\world\loot\LootContext;
use pocketmine\world\loot\LootTable;
use pocketmine\world\loot\pool\LootPool;

class LootEntry{
	use LootConditionHandlingTrait;

	/**
	 * @param EntryFunction[] $functions
	 * @param LootCondition[] $conditions
	 * @param LootPool[]      $pools
	 */
	public function __construct(
		protected LootEntryType $type,
		protected ItemStackData|LootTable|null $entry,
		protected int $weight = 1,
		protected int $quality = 1,
		protected array $functions = [],
		array $conditions = [],
		protected array $pools = []
	) {
		if($weight < 1){
			throw new \InvalidArgumentException("Weight must be at least of 1");
		}
		Utils::validateArrayValueType($functions, function(EntryFunction $_) : void{});
		Utils::validateArrayValueType($conditions, function(LootCondition $_) : void{});
		Utils::validateArrayValueType($pools, function(LootPool $_) : void{});

		$this->conditions = $conditions;
	}

	public function getType() : LootEntryType{
		return $this->type;
	}

	public function getEntry() : ItemStackData|LootTable|null{
		return $this->entry;
	}

	public function getWeight() : int{
		return $this->weight;
	}

	public function getQuality() : int{
		return $this->quality;
	}

	/**
	 * @return EntryFunction[]
	 */
	public function getFunctions() : array{
		return $this->functions;
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
		$items = $this->type->generate($this, $context);

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
