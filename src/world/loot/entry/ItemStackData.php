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

namespace pocketmine\world\loot\entry;

use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\item\Item;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use pocketmine\world\loot\entry\function\EntryFunction;
use pocketmine\world\loot\LootContext;
use function ceil;
use function min;

final class ItemStackData{

	public function __construct(public string $name){ }

	/**
	 * @param EntryFunction[] $functions
	 *
	 * @return Item[]
	 */
	public function generate(LootContext $context, array $functions = []) : array{
		Utils::validateArrayValueType($functions, function(EntryFunction $_) : void{});

		$meta = 0;
		$count = 1;

		$items = [];

		foreach($functions as $function){
			$function->onPreCreation($context, $meta, $count);
		}

		if($count > 0){
			try{
				//TODO: This will not deserialize >1.12 blocks
				$item = GlobalItemDataHandlers::getDeserializer()->deserializeStack(GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataString(
					$this->name,
					$meta,
					$count,
					null
				));

				foreach($functions as $function){
					$item = $function->onCreation($context, $item);
				}

				//split up stacks
				$maxStackSize = $item->getMaxStackSize();
				$stacks = (int) ceil($count / $maxStackSize);
				if($stacks > 1){
					for($i = 0; $i < $stacks; $i++){
						$items[] = $item->pop(min($maxStackSize, $item->getCount()));
					}
				}else{
					$items[] = $item;
				}

				return $items;
			}catch(ItemTypeDeserializeException $e){
				//probably unknown item
			}
		}

		return $items;
	}
}
