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

namespace pocketmine\item;

use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\SplashPotion as SplashPotionEntity;
use pocketmine\entity\projectile\Throwable;
use pocketmine\player\Player;

class SplashPotion extends ProjectileItem{

	private PotionType $potionType = PotionType::WATER;

	public function __construct(
		ItemIdentifier $identifier,
		string $name = "Splash Potion",
		array $enchantmentTags = [],
		private bool $linger = false
	){
		//TODO: remove unnecessary default parameters in PM6, they remain because backward compatibility
		parent::__construct($identifier, $name, $enchantmentTags);
	}

	protected function describeState(RuntimeDataDescriber $w) : void{
		$w->enum($this->potionType);
	}

	public function getType() : PotionType{ return $this->potionType; }

	/**
	 * @return $this
	 */
	public function setType(PotionType $type) : self{
		$this->potionType = $type;
		return $this;
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	protected function createEntity(Location $location, Player $thrower) : Throwable{
		$projectile = new SplashPotionEntity($location, $thrower, $this->potionType);
		$projectile->setLinger($this->linger);
		return $projectile;
	}

	public function getThrowForce() : float{
		return 0.5;
	}

	/**
	 * Returns whether this splash potion will create an area-effect cloud when it lands on it's projectile form.
	 */
	public function willLinger() : bool{
		return $this->linger;
	}
}
