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

use pocketmine\block\utils\DyeColor;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\world\loot\entry\function\EntryFunction;
use pocketmine\world\loot\LootContext;
use function array_values;
use function count;

class RandomDye extends EntryFunction{

	public function onCreation(LootContext $context, Item $item) : void{
		if(match($item->getTypeId()){
			ItemTypeIds::LEATHER_CAP,
			ItemTypeIds::LEATHER_TUNIC,
			ItemTypeIds::LEATHER_PANTS,
			ItemTypeIds::LEATHER_BOOTS => true,
			default => false
		} && $item instanceof Armor){
			$colors = array_values(DyeColor::getAll());
			$item->setCustomColor(($colors[$context->getRandom()->nextBoundedInt(count($colors))])->getRgbValue());
		}
	}
}
