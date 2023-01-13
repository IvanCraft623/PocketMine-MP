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
use function count;

class LootTable implements \JsonSerializable{

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

	/**
	 * Returns an array of loot table properties that can be serialized to json.
	 *
	 * @return mixed[]
	 * @phpstan-return array{
	 * 	pools?: array<array{
	 * 		rolls: int|array{min: int, max: int},
	 * 		entries?: array<array{
	 * 			type: string,
	 * 			name?: string,
	 * 			weight?: int,
	 * 			quality?: int,
	 * 			functions?: array<array{function: string, ...}>,
	 * 			conditions?: array<array{condition: string, ...}>
	 * 		},
	 * 		conditions?: array<array{condition: string, ...}>
	 * 	}>
	 * }
	 */
	final public function jsonSerialize() : array{
		$data = [];

		if(count($this->pools) !== 0){
			foreach($this->pools as $pool){
				$data["pools"][] = $pool->jsonSerialize();
			}
		}

		return $data;
	}

	/**
	 * Returns a LootTable from properties created in an array by {@link LootTable#jsonSerialize}
	 *
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
	 * 			conditions?: array<array{condition: string, ...}>
	 * 		},
	 * 		conditions?: array<array{condition: string, ...}>
	 * 	}>
	 * } $data
	 */
	public static function jsonDeserialize(array $data) : LootTable{
		$pools = [];

		if(isset($data["pools"])){
			foreach($data["pools"] as $poolData){
				$pools[] = LootPool::jsonDeserialize($poolData);
			}
		}

		return new LootTable($pools);
	}
}
