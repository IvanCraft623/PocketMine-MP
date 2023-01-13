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
use pocketmine\world\loot\condition\LootCondition;
use pocketmine\world\loot\condition\LootConditionHandlingTrait;
use pocketmine\world\loot\entry\LootEntry;
use function gettype;
use function is_array;
use function is_int;

class LootPool implements \JsonSerializable{
	use LootConditionHandlingTrait;

	/**
	 * @param LootEntry[]     $entries
	 * @param LootCondition[] $conditions
	 */
	public function __construct(
		protected array $entries,
		protected int $minRolls = 1,
		protected int $maxRolls = 1,
		array $conditions = []
	) {
		Utils::validateArrayValueType($entries, function(LootEntry $_) : void{});
		Utils::validateArrayValueType($conditions, function(LootCondition $_) : void{});

		if($minRolls < 0){
			throw new \InvalidArgumentException("minRolls cannot be less than 0");
		}
		if($minRolls > $maxRolls){
			throw new \InvalidArgumentException("minRolls is larger that maxRolls");
		}

		$this->conditions = $conditions;
	}

	public function getMinRolls() : int{
		return $this->minRolls;
	}

	public function getMaxRolls() : int{
		return $this->maxRolls;
	}

	/**
	 * @return LootEntry[]
	 */
	public function getEntries() : array{
		return $this->entries;
	}

	/**
	 * @return Item[]
	 */
	public function generate(LootContext $context) : array{
		$items = [];

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

	public function jsonSerialize() : array{
		$data = [];

		if($this->minRolls === $this->maxRolls){
			$data["rolls"] = $this->minRolls;
		}else{
			$data["rolls"] = [
				"min" => $this->minRolls,
				"max" => $this->maxRolls
			];
		}

		foreach($this->conditions as $condition){
			$data["conditions"][] = $condition->jsonSerialize();
		}

		foreach($this->entries as $entry){
			$data["entries"][] = $entry->jsonSerialize();
		}

		return $data;
	}

	public static function jsonDeserialize(array $data) : LootPool{
		$rolls = $data["rolls"] ?? 1;
		if(is_int($rolls)){
			$minRolls = $maxRolls = $rolls;
		}elseif(is_array($rolls)){
			$minRolls = $rolls["min"] ?? 1;
			$maxRolls = $rolls["max"] ?? 1;
		}else{
			throw new \InvalidArgumentException("Expected int or array for \"rolls\", " . gettype($rolls) . " given");
		}

		$entries = [];
		if(isset($data["entries"])){
			foreach($data["entries"] as $entryData){
				$entries[] = LootEntry::jsonDeserialize($entryData);
			}
		}

		$conditions = [];
		if(isset($data["conditions"])){
			foreach($data["conditions"] as $conditionData){
				$conditions[] = LootCondition::jsonDeserialize($conditionData);
			}
		}

		return new LootPool($entries, $minRolls, $maxRolls, $conditions);
	}
}
