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
use pocketmine\world\loot\entry\function\EntryFunction;
use pocketmine\world\loot\LootContext;
use function array_values;

class EnchantRandomly extends EntryFunction{ 

	public function __construct(private bool $treasureEnchants = false) {
	}

	public function onCreation(LootContext $context, Item $item) : void{
		//TODO: treasure enchantments check
		//TODO: check compatibility
		$enchants = array_values(VanillaEnchantments::getAll());
		$item->addEnchantment(new EnchantmentInstance($enchants[$context->getRandom()->nextBoundedInt(count($enchants) - 1)]));
	}

	public function jsonSerialize() : array{
		$data = parent::jsonSerialize();

		if($this->treasureEnchants){
			$data["treasure"] = true;
		}

		return $data;
	}
}
