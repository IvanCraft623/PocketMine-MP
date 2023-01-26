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

use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\world\loot\condition\LootCondition;
use pocketmine\world\loot\entry\function\EntryFunction;
use pocketmine\world\loot\LootContext;
use function array_values;
use function count;

class EnchantRandomlyFunction extends EntryFunction{

	/**
	 * @param LootCondition[] $conditions
	 */
	public function __construct(private bool $treasureEnchants = false, array $conditions = []){
		parent::__construct($conditions);
	}

	public function onCreation(LootContext $context, Item $item) : Item{
		//TODO: treasure enchantments check
		//TODO: check compatibility
		$enchants = array_values(VanillaEnchantments::getAll());
		$item->addEnchantment(new EnchantmentInstance($enchants[$context->getRandom()->nextBoundedInt(count($enchants))]));

		return parent::onCreation($context, $item);
	}

	/**
	 * Returns an array of properties that can be serialized to json.
	 *
	 * @phpstan-return array{
	 * 	function: string,
	 * 	treasure?: bool
	 * }
	 */
	public function jsonSerialize() : array{
		$data = parent::jsonSerialize();

		if($this->treasureEnchants){
			$data["treasure"] = true;
		}

		return $data;
	}
}
