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

use pocketmine\item\Item;
use pocketmine\utils\EnumTrait;
use pocketmine\world\loot\LootContext;
use pocketmine\world\loot\LootTable;

/**
 * This doc-block is generated automatically, do not modify it manually.
 * This must be regenerated whenever registry members are added, removed or changed.
 * @see build/generate-registry-annotations.php
 * @generate-registry-docblock
 *
 * @method static LootEntryType EMPTY()
 * @method static LootEntryType ITEM()
 * @method static LootEntryType LOOT_TABLE()
 */
final class LootEntryType{
	use EnumTrait {
		__construct as Enum___construct;
	}

	protected static function setup() : void{
		self::registerAll(
			new self("item", function(LootEntry $entry, LootContext $context) : array{
				$stack = $entry->getEntry();
				if(!$stack instanceof ItemStackData){
					throw new \InvalidArgumentException("Entry should be ItemStackData type");
				}
				$item = $stack->generate($context, $entry->getFunctions());
				return $item === null ? [] : [$item];
			}),
			new self("loot_table", function(LootEntry $entry, LootContext $context) : array{
				$table = $entry->getEntry();
				if(!$table instanceof LootTable){
					throw new \InvalidArgumentException("Entry should be LootTable type");
				}
				return $table->generate($context);
			}),
			new self("empty", fn() => [])
		);
	}

	/**
	 * @phpstan-param \Closure(LootEntry, LootContext) : ?array<Item> $resultGetter
	 */
	private function __construct(
		string $enumName,
		private \Closure $resultGetter
	){
		$this->Enum___construct($enumName);
	}

	/**
	 * @return Item[]
	 */
	public function generate(LootEntry $entry, LootContext $context) : array{
		return ($this->resultGetter)($entry, $context);
	}
}
