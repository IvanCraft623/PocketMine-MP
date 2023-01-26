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
use pocketmine\world\loot\LootContext;

abstract class EntryFunction implements \JsonSerializable{

	public function onPreCreation(LootContext $context, int &$meta, int &$count) : void{
	}

	public function onCreation(LootContext $context, Item $item) : Item{
		return $item;
	}

	/**
	 * Returns an array of an entry function properties that can be serialized to json.
	 *
	 * @phpstan-return array{
	 * 	function: string
	 * }
	 */
	public function jsonSerialize() : array{
		return ["function" => EntryFunctionFactory::getInstance()->getSaveId($this::class)];
	}

	/**
	 * Returns a EntryFunction from properties created in an array by {@link EntryFunction#jsonSerialize}
	 *
	 * @phpstan-param array{
	 * 	function: string
	 * } $data
	 */
	public static function jsonDeserialize(array $data) : EntryFunction{
		return EntryFunctionFactory::getInstance()->createFromData($data) ?? throw new \InvalidArgumentException("EntryFunction \"" . $data["function"] . "\" is not registered");
		;
	}
}
