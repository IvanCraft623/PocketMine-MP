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

use pocketmine\data\bedrock\SuspiciousStewTypeIdMap;
use pocketmine\item\Item;
use pocketmine\item\SuspiciousStew;
use pocketmine\item\SuspiciousStewType;
use pocketmine\utils\Utils;
use pocketmine\world\loot\entry\function\EntryFunction;
use pocketmine\world\loot\LootContext;
use function count;

class SetSuspiciousStewTypeFunction extends EntryFunction{

	/**
	 * @param SuspiciousStewType[] $types
	 * @phpstan-param non-empty-list<SuspiciousStewType> $types
	 */
	public function __construct(protected array $types){
		Utils::validateArrayValueType($types, function(SuspiciousStewType $_) : void{});
	}

	public function onCreation(LootContext $context, Item $item) : void{
		if($item instanceof SuspiciousStew){
			$item->setType($this->types[$context->getRandom()->nextBoundedInt(count($this->types))]);
		}
	}

	/**
	 * Returns an array of properties that can be serialized to json.
	 *
	 * @phpstan-return array{
	 * 	function: string,
	 * 	effects: array<array{id: int}>
	 * }
	 */
	public function jsonSerialize() : array{
		$data = parent::jsonSerialize();

		foreach($this->types as $type){
			$data["effects"][] = ["id" => SuspiciousStewTypeIdMap::getInstance()->toId($type)];
		}

		return $data;
	}
}
