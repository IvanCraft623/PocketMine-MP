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

use pocketmine\world\loot\entry\ItemStackData;
use pocketmine\world\loot\entry\LootEntry;
use pocketmine\world\loot\LootPool;
use pocketmine\world\loot\LootTable;

/**
 * Bunch of functions to convert loot tables into properties that can be serialized to json.
 */
final class LootTableSerializerHelper{

	/**
	 * @return mixed[]
	 * @phpstan-return array{
	 * 	pools: array<array{
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
	 * }
	 */
	public static function serializeLootTable(LootTable $table) : array{
		$data = [];

		$pools = $table->getPools();
		foreach($pools as $pool){
			$data["pools"][] = self::serializeLootPool($pool);
		}

		return $data;
	}

	/**
	 * @return mixed[]
	 * @phpstan-return array{
	 * 	rolls: int|array{min: int, max: int},
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
	 * }
	 */
	public static function serializeLootPool(LootPool $pool) : array{
		$data = [];

		$minRolls = $pool->getMinRolls();
		$maxRolls = $pool->getMaxRolls();
		if($minRolls === $maxRolls){
			$data["rolls"] = $minRolls;
		}else{
			$data["rolls"] = [
				"min" => $minRolls,
				"max" => $maxRolls
			];
		}

		$entries = $pool->getEntries();
		foreach($entries as $entry){
			$data["entries"][] = self::serializeLootEntry($entry);
		}

		$conditions = $pool->getConditions();
		foreach($conditions as $condition){
			$data["conditions"][] = $condition->jsonSerialize();
		}

		return $data;
	}

	/**
	 * @return mixed[]
	 * @phpstan-return array{
	 * 	type: string,
	 * 	name?: string,
	 * 	weight?: int,
	 * 	quality?: int,
	 * 	functions?: array<array{function: string, ...}>,
	 * 	conditions?: array<array{condition: string, ...}>,
	 * 	...
	 * }
	 */
	public static function serializeLootEntry(LootEntry $entry) : array{
		$data = [];

		$data["type"] = $entry->getType()->name();

		$e = $entry->getEntry();
		if($e instanceof ItemStackData){
			$data["name"] = $e->name;
		}elseif($e instanceof LootTable){
			#TODO: LootTableFactory
		}

		$weight = $entry->getWeight();
		if($weight !== 1){
			$data["weight"] = $weight;
		}

		$quality = $entry->getQuality();
		if($quality !== 1){
			$data["quality"] = $quality;
		}

		$functions = $entry->getFunctions();
		foreach($functions as $function){
			$data["functions"][] = $function->jsonSerialize();
		}

		$conditions = $entry->getConditions();
		foreach($conditions as $condition){
			$data["conditions"][] = $condition->jsonSerialize();
		}

		$pools = $entry->getPools();
		foreach($pools as $pool){
			$data["pools"][] = self::serializeLootPool($pool);
		}

		return $data;
	}
}
