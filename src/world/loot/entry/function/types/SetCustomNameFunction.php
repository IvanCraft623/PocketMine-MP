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

namespace pocketmine\world\loot\entry\function\types;

use pocketmine\item\Item;
use pocketmine\world\loot\condition\LootCondition;
use pocketmine\world\loot\entry\function\EntryFunction;
use pocketmine\world\loot\LootContext;

class SetCustomNameFunction extends EntryFunction{

	/**
	 * @param LootCondition[] $conditions
	 */
	public function __construct(private string $name, array $conditions = []){
		parent::__construct($conditions);
	}

	public function onCreation(LootContext $context, Item $item) : Item{
		$item->setCustomName($this->name);

		return parent::onCreation($context, $item);
	}

	/**
	 * Returns an array of properties that can be serialized to json.
	 *
	 * @phpstan-return array{
	 * 	function: string,
	 * 	name: string,
	 * 	conditions?: array<array{condition: string, ...}>
	 * }
	 */
	public function jsonSerialize() : array{
		$data = parent::jsonSerialize();

		$data["name"] = $this->name;

		return $data;
	}
}
