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

use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\world\loot\condition\LootCondition;
use pocketmine\world\loot\entry\function\EntryFunction;
use pocketmine\world\loot\LootContext;
use function ceil;

class SetDamageFunction extends EntryFunction{

	/**
	 * @param LootCondition[] $conditions
	 */
	public function __construct(private float $min, private float $max, array $conditions = []){
		if($min < 0 || $min > 1){
			throw new \InvalidArgumentException("Min must be between 0.0 and 1.0");
		}
		if($max < 0 || $max > 1){
			throw new \InvalidArgumentException("Max must be between 0.0 and 1.0");
		}
		if($min > $max){
			throw new \InvalidArgumentException("Min is larger that max");
		}
		parent::__construct($conditions);
	}

	public function onCreation(LootContext $context, Item $item) : Item{
		if($item instanceof Durable){
			$durability = $item->getMaxDurability() - $item->getDamage();
			$item->setDamage($durability - (int) ceil(($context->getRandom()->nextFloat() * ($this->max - $this->min) + $this->min) * $durability));
		}

		return parent::onCreation($context, $item);
	}

	/**
	 * Returns an array of properties that can be serialized to json.
	 *
	 * @phpstan-return array{
	 * 	function: string,
	 * 	damage: float|array<string, float>
	 * }
	 */
	public function jsonSerialize() : array{
		$data = parent::jsonSerialize();

		if($this->min === $this->max){
			$data["damage"] = $this->min;
		}else{
			$data["damage"] = [
				"min" => $this->min,
				"max" => $this->max
			];
		}

		return $data;
	}
}
