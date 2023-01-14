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

namespace pocketmine\world\loot\json;

use pocketmine\data\SavedDataLoadingException;
use pocketmine\world\loot\condition\LootCondition;
use pocketmine\world\loot\entry\function\EntryFunction;
use pocketmine\world\loot\entry\LootEntry;
use pocketmine\world\loot\entry\LootEntryType;
use pocketmine\world\loot\LootPool;
use pocketmine\world\loot\LootTable;

final class LootTableDeserializerHelper{

	/**
	 * @param mixed[] $data
	 * @phpstan-param array{
	 * 	pools?: array<array{
	 * 		rolls: int|array{min: int, max: int},
	 * 		entries?: array<array{
	 * 			type: string,
	 * 			name?: string,
	 * 			weight?: int,
	 * 			quality?: int,
	 * 			functions?: array<array{function: string, ...}>,
	 * 			conditions?: array<array{condition: string, ...}>,
	 * 			...
	 * 		}>,
	 * 		conditions?: array<array{condition: string, ...}>
	 * 	}>
	 * } $data
	 */
	public function deserializeLootTable(array $data) : LootTable{
		$pools = [];

		if(isset($data["pools"])){
			foreach($data["pools"] as $poolData){
				$pools[] = self::deserializeLootPool($poolData);
			}
		}

		return new LootTable($pools);
	}

	/**
	 * @param mixed[] $data
	 * @phpstan-param array{
	 * 	rolls?: int|array{min: int, max: int},
	 * 	entries?: array<array{
	 * 		type: string,
	 * 		name?: string,
	 * 		weight?: int,
	 * 		quality?: int,
	 * 		functions?: array<array{function: string, ...}>,
	 * 		conditions?: array<array{condition: string, ...}>,
	 * 		...
	 * 	}>,
	 * 	conditions?: array<array{condition: string, ...}>
	 * } $data
	 */
	public function deserializeLootPool(array $data) : LootTable{
		$rolls = $data["rolls"] ?? 1;
		if(is_int($rolls)){
			$minRolls = $maxRolls = $rolls;
		}else{
			$minRolls = $rolls["min"];
			$maxRolls = $rolls["max"];
		}

		$entries = [];
		if(isset($data["entries"])){
			foreach($data["entries"] as $entryData){
				$entries[] = self::deserializeLootEntry($entryData);
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

	/**
	 * @param mixed[] $data
	 * @phpstan-param array{
	 * 	type: string,
	 * 	name?: string,
	 * 	weight?: int,
	 * 	quality?: int,
	 * 	functions?: array<array{function: string, ...}>,
	 * 	conditions?: array<array{condition: string, ...}>,
	 * 	...
	 * } $data
	 */
	public static function deserializeLootEntry(array $data) : LootEntry{
		$entry = null;
		switch($data["type"]){
			case "item":
				$type = LootEntryType::ITEM();

				if(!isset($data["name"])){
					throw new SavedDataLoadingException("Expected key \"name\"");
				}
				$entry = new ItemStackData($data["name"]);
				break;
			case "loot_table":
				$type = LootEntryType::LOOT_TABLE();
				#TODO: LootTableFactory
				break;
			case "empty":
				$type = LootEntryType::EMPTY();
				break;
			default:
				throw new SavedDataLoadingException("Type \"" . $data["type"] . "\" doesn't exists");
		}

		$weight = $data["weight"] ?? 1;
		$quality = $data["quality"] ?? 1;

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

		$pools = [];
		if(isset($data["pools"])){
			foreach($data["pools"] as $poolsData){
				$functions[] = self::deserializeLootPool($poolsData);
			}
		}

		return new LootEntry($type, $entry, $weight, $quality, $functions, $conditions, $pools);
	}
}
