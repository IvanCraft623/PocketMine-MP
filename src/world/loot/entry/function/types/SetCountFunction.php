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

use pocketmine\world\loot\condition\LootCondition;
use pocketmine\world\loot\entry\function\EntryFunction;
use pocketmine\world\loot\LootContext;

class SetCountFunction extends EntryFunction{

	/**
	 * @param LootCondition[] $conditions
	 */
	public function __construct(private int $min, private int $max, array $conditions = []){
		if($min < 0){
			throw new \InvalidArgumentException("Min cannot be less than 0");
		}
		if($min > $max){
			throw new \InvalidArgumentException("Min is larger that max");
		}
		parent::__construct($conditions);
	}

	public function onPreCreation(LootContext $context, int &$meta, int &$count) : void{
		$count = $context->getRandom()->nextRange($this->min, $this->max);
	}

	/**
	 * Returns an array of properties that can be serialized to json.
	 *
	 * @phpstan-return array{
	 * 	function: string,
	 * 	count: int|array<string, int>,
	 * 	conditions?: array<array{condition: string, ...}>
	 * }
	 */
	public function jsonSerialize() : array{
		$data = parent::jsonSerialize();

		if($this->min === $this->max){
			$data["count"] = $this->min;
		}else{
			$data["count"] = [
				"min" => $this->min,
				"max" => $this->max
			];
		}

		return $data;
	}
}
