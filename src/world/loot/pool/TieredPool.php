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

namespace pocketmine\world\loot\pool;

use pocketmine\item\Item;
use pocketmine\world\loot\condition\LootCondition;
use pocketmine\world\loot\entry\LootEntry;
use pocketmine\world\loot\LootContext;
use function count;

class TieredPool extends LootPool{

	/**
	 * @param LootEntry[]     $entries
	 * @param LootCondition[] $conditions
	 */
	public function __construct(
		array $entries,
		protected int $initialRange = 1,
		protected int $bonusRolls = 0,
		protected float $bonusChance = 0.0,
		array $conditions = []
	) {
		if($initialRange > count($entries)){
			throw new \InvalidArgumentException("initialRange is greater than entries count");
		}
		if($initialRange < 1){
			throw new \InvalidArgumentException("initialRange must be greater than 1");
		}
		if($bonusRolls < 0){
			throw new \InvalidArgumentException("bonusRolls cannot be less than 0");
		}

		parent::__construct($entries, $conditions);
	}

	public function getInitialRange() : int{
		return $this->initialRange;
	}

	public function getBonusRolls() : int{
		return $this->bonusRolls;
	}

	public function getBonusChance() : float{
		return $this->bonusChance;
	}

	/**
	 * @return Item[]
	 */
	public function generate(LootContext $context) : array{
		//tiered pools ignore entry conditions
		$index = $context->getRandom()->nextRange(1, $this->initialRange);
		if($this->bonusRolls > 0){
			for($i = 0; $i < $this->bonusRolls; $i++){
				if($context->getRandom()->nextFloat() <= $this->bonusChance){
					$index++;
				}
			}
		}

		if(isset($this->entries[$index])){
			return $this->entries[$index]->generate($context);
		}
		return [];
	}
}
