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

namespace pocketmine\world\loot\entry\function;

use pocketmine\item\Item;
use pocketmine\utils\Utils;
use pocketmine\world\loot\condition\LootCondition;
use pocketmine\world\loot\condition\LootConditionHandlingTrait;
use pocketmine\world\loot\LootContext;

abstract class EntryFunction implements \JsonSerializable{
	use LootConditionHandlingTrait;

	/**
	 * @param LootCondition[] $conditions
	 */
	public function __construct(array $conditions) {
		Utils::validateArrayValueType($conditions, function(LootCondition $_) : void{});
		$this->conditions = $conditions;
	}

	public function onPreCreation(LootContext $context, int &$meta, int &$count) : void{
	}

	public function onCreation(LootContext $context, Item $item) : Item{
		return $item;
	}

	/**
	 * Returns an array of an entry function properties that can be serialized to json.
	 *
	 * @phpstan-return array{
	 * 	function: string,
	 * 	conditions?: array<array{condition: string, ...}>
	 * }
	 */
	public function jsonSerialize() : array{
		$data = [];

		$data["function"] = EntryFunctionFactory::getInstance()->getSaveId($this::class);
		foreach($this->conditions as $condition){
			$data["conditions"][] = $condition->jsonSerialize();
		}

		return $data;
	}

	/**
	 * Returns a EntryFunction from properties created in an array by {@link EntryFunction#jsonSerialize}
	 *
	 * @phpstan-param array{
	 * 	function: string,
	 * 	conditions?: array<array{condition: string, ...}>
	 * } $data
	 */
	public static function jsonDeserialize(array $data) : EntryFunction{
		return EntryFunctionFactory::getInstance()->createFromData($data) ?? throw new \InvalidArgumentException("EntryFunction \"" . $data["function"] . "\" is not registered");
		;
	}
}
