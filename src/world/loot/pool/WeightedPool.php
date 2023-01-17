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

namespace pocketmine\world\loot\pool;

use pocketmine\item\Item;
use pocketmine\world\loot\condition\LootCondition;
use pocketmine\world\loot\entry\LootEntry;
use pocketmine\world\loot\LootContext;

class WeightedPool extends LootPool{

	/**
	 * @param LootEntry[]     $entries
	 * @param LootCondition[] $conditions
	 */
	public function __construct(
		array $entries,
		protected int $minRolls = 1,
		protected int $maxRolls = 1,
		array $conditions = []
	) {
		if($minRolls < 0){
			throw new \InvalidArgumentException("minRolls cannot be less than 0");
		}
		if($minRolls > $maxRolls){
			throw new \InvalidArgumentException("minRolls is larger that maxRolls");
		}

		parent::__construct($entries, $conditions);
	}

	public function getMinRolls() : int{
		return $this->minRolls;
	}

	public function getMaxRolls() : int{
		return $this->maxRolls;
	}

	/**
	 * @return Item[]
	 */
	public function generate(LootContext $context) : array{
		$items = [];

		if(count($this->entries) === 0){
			return $items;
		}

		$rolls = $context->getRandom()->nextRange($this->minRolls, $this->maxRolls);
		if($rolls < 1){
			return $items;
		}

		$entries = [];
		$totalWeight = 0;
		foreach($this->entries as $entry){
			if($entry->evaluateConditions($context)){
				$totalWeight += $entry->getWeight();
				$entries[] = $entry;
			}
		}

		for($i = 0; $i < $rolls; $i++){
			$selected = $context->getRandom()->nextRange(1, $totalWeight);
			$currentWeight = 0;
			foreach($entries as $entry){
				$currentWeight += $entry->getWeight();
				if($selected <= $currentWeight){
					foreach($entry->generate($context) as $item){
						$items[] = $item;
					}
					break;
				}
			}
		}

		return $items;
	}
}