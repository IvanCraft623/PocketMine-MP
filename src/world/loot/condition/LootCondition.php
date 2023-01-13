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

namespace pocketmine\world\loot\condition;

use pocketmine\world\loot\LootContext;

abstract class LootCondition implements \JsonSerializable{

	abstract public function evaluate(LootContext $context) : bool;

	/**
	 * Returns an array of loot condition properties that can be serialized to json.
	 *
	 * @phpstan-return array{
	 * 	condition: string
	 * }
	 */
	public function jsonSerialize() : array{
		return ["condition" => LootConditionFactory::getInstance()->getSaveId($this::class)];
	}

	/**
	 * Returns a LootCondition from properties created in an array by {@link LootCondition#jsonSerialize}
	 *
	 * @phpstan-param array{
	 * 	condition: string
	 * } $data
	 */
	public static function jsonDeserialize(array $data) : LootCondition{
		return LootConditionFactory::getInstance()->createFromData($data) ?? throw new \InvalidArgumentException("LootCondition \"" . $data["condition"] . "\" is not registered");
		;
	}
}
