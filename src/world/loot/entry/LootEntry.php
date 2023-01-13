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
use function count;

class LootEntry implements \JsonSerializable{
	use LootConditionHandlingTrait;

	/**
	 * @param EntryFunction[] $functions
	 * @param LootCondition[] $conditions
	 */
	public function __construct(
		protected LootEntryType $type,
		protected ItemStackData|LootTable|null $entry,
		protected int $weight = 1,
		protected int $quality = 1,
		protected array $functions = [],
		array $conditions = []
	) {
		if($weight < 1){
			throw new \IvnvalidArgumentException("Weight must be at least of 1");
			
		}
		Utils::validateArrayValueType($functions, function(EntryFunction $_) : void{});
		Utils::validateArrayValueType($conditions, function(LootCondition $_) : void{});

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
	 * @return Item[]
	 */
	public function generate(LootContext $context) : array{
		return $this->type->generate($this, $context);
	}

	public function jsonSerialize() : array{
		$data = [];

		$data["type"] = $this->type->name();
		if($this->entry instanceof ItemStackData){
			$data["name"] = $this->entry->name;
		}elseif($this->entry instanceof LootTable){
			#TODO: LootTableFactory
		}

		if($this->weight !== 1){
			$data["weight"] = $this->weight;
		}

		if($this->quality !== 1){
			$data["quality"] = $this->quality;
		}

		foreach($this->functions as $function){
			$data["functions"][] = $function->jsonSerialize();
		}

		foreach($this->conditions as $condition){
			$data["conditions"][] = $condition->jsonSerialize();
		}

		return $data;
	}

	public static function jsonDeserialize(array $data) : LootEntry{
		$type = match($data["type"] ?? null){
			"item" => LootEntryType::ITEM(),
			"loot_table" => LootEntryType::LOOT_TABLE(),
			"empty" => LootEntryType::EMPTY(),
			default => throw new \InvalidArgumentException("Type doesn't exists")
		};

		switch($data["type"] ?? null){
			case "item":
				$type = LootEntryType::ITEM();

				if(!isset($data["name"])){
					throw new \InvalidArgumentException("Expected key \"name\"");
				}
				$entry = new ItemStackData($data["name"]);
				break;
			case "loot_table":
				$type = LootEntryType::LOOT_TABLE();
				#TODO: LootTableFactory
				break;
			case "empty":
				$type = LootEntryType::EMPTY();
				$entry = null;
				break;
			default:
				throw new \InvalidArgumentException("Type doesn't exists");
				break;
		}

		$weight = (int) ($data["weight"] ?? 1);
		$quality = (int) ($data["quality"] ?? 1);

		$functions = [];
		if(isset($data["functions"])){
			foreach($data["functions"] as $functionData){
				$functions[] = EntryFunction::jsonDeserialize($functionData);
			}
		}
		$conditions = [];
		if(isset($data["conditions"])){
			foreach($data["conditions"] as $conditionData){
				$conditions[] = LootCondition::jsonDeserialize($conditionData);
			}
		}

		return new LootEntry($type, $entry, $weight, $quality, $functions, $conditions);
	}
}