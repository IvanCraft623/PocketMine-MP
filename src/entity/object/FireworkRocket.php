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

namespace pocketmine\entity\object;

use pocketmine\entity\animation\FireworkParticlesAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\FireworkRocket as FireworkItem;
use pocketmine\math\VoxelRayTrace;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\world\sound\FireworkLaunchSound;
use pocketmine\world\sound\FireworkTwinkleSound;
use function count;
use function sqrt;

class FireworkRocket extends Entity{

	public static function getNetworkTypeId() : string{ return EntityIds::FIREWORKS_ROCKET; }

	/* Maximum number of ticks this will live for. */
	protected int $lifeTicks;

	protected FireworkItem $item;

	public function __construct(Location $location, int $lifeTicks, FireworkItem $item, ?CompoundTag $nbt = null){
		if ($lifeTicks < 0) {
			throw new \InvalidArgumentException("Life ticks cannot be negative");
		}
		$this->lifeTicks = $lifeTicks;
		$this->item = $item;

		parent::__construct($location, $nbt);
	}

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(0.25, 0.25); }

	protected function getInitialDragMultiplier() : float{ return 0.0; }

	protected function getInitialGravity() : float{ return 0.0; }

	/**
	 * Returns maximum number of ticks this will live for.
	 */
	public function getLifeTicks() : int{
		return $this->lifeTicks;
	}

	/**
	 * Sets maximum number of ticks this will live for.
	 *
	 * @return $this
	 */
	public function setLifeTicks(int $lifeTicks) : self{
		if ($lifeTicks < 0) {
			throw new \InvalidArgumentException("Life ticks cannot be negative");
		}
		$this->lifeTicks = $lifeTicks;
		return $this;
	}

	public function getItem() : FireworkItem{
		return clone $this->item;
	}

	/** @return $this */
	public function setItem(FireworkItem $item) : self{
		$this->item = clone $item;
		return $this;
	}

	/**
	 * TODO: The entity should be saved and loaded, but this is not possible.
	 * @see https://bugs.mojang.com/browse/MCPE-165230
	 */
	public function canSaveWithChunk() : bool{
		return false;
	}

	protected function onFirstUpdate(int $currentTick) : void{
		parent::onFirstUpdate($currentTick);

		$this->broadcastSound(new FireworkLaunchSound());
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if(!$this->isFlaggedForDespawn()){
			$this->motion->x *= 1.15;
			$this->motion->y += 0.04;
			$this->motion->z *= 1.15;

			if($this->ticksLived >= $this->lifeTicks){
				$this->explode();
			}
		}

		return $hasUpdate;
	}

	public function explode() : void{
		$explosions = $this->item->getExplosions();
		if(($expCount = count($explosions)) !== 0){
			$this->broadcastAnimation(new FireworkParticlesAnimation($this));
			foreach($explosions as $explosion){
				$this->broadcastSound($explosion->getType()->getSound());
				if($explosion->willTwinkle()){
					$this->broadcastSound(new FireworkTwinkleSound());
				}
			}

			$force = ($expCount * 2) + 5;
			foreach($this->getWorld()->getCollidingEntities($this->getBoundingBox()->expandedCopy(5, 5, 5), $this) as $entity){
				if(!$entity instanceof Living){
					continue;
				}

				$position = $entity->getEyePos();
				$distance = $position->distance($this->location);
				if($distance > 5){
					continue;
				}

				$world = $this->getWorld();

				//check for obstructing blocks
				foreach(VoxelRayTrace::betweenPoints($this->location, $position) as $pos){
					if($world->getBlockAt((int) $pos->x, (int) $pos->y, (int) $pos->z)->isSolid()){
						continue 2;
					}
				}

				$ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $force * sqrt((5 - $distance) / 5));
				$entity->attack($ev);
			}
		}

		$this->flagForDespawn();
	}

	public function canBeCollidedWith() : bool{
		return false;
	}

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);

		//TODO: Use the appropriate constant
		$properties->setCompoundTag(EntityMetadataProperties::MINECART_DISPLAY_BLOCK, new CacheableNbt($this->item->getNamedTag()));
	}
}