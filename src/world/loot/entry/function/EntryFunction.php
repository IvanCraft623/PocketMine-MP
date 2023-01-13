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

	public function onCreation(LootContext $context, Item $item) : void{
	}

	public function jsonSerialize() : array{
		return ["function" => EntryFunctionFactory::getInstance()->getSaveId($this::class)];
	}

	public static function jsonDeserialize(array $data) : EntryFunction{
		return EntryFunctionFactory::getInstance()->createFromData($data) ?? throw new \InvalidArgumentException("EntryFunction \"" . ($data["function"] ?? "unknown") . "\" is not registered");
		;
	}
}
