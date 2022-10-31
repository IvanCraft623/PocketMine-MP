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

namespace pocketmine\entity\projectile;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Trident as TridentItem;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\world\sound\TridentHitGroundSound;
use pocketmine\world\sound\TridentHitSound;
use pocketmine\world\sound\TridentReturnSound;

class Trident extends Projectile{

	public static function getNetworkTypeId() : string{ return EntityIds::THROWN_TRIDENT; }

	protected TridentItem $item;

	protected float $damage = 8.0;

	protected bool $canCollide = true;

	protected bool $spawnedInCreative;

	protected bool $isReturning = false;

	protected ?int $favoredSlot = null;

	public function __construct(
		Location $location,
		TridentItem $item,
		?Entity $shootingEntity,
		?CompoundTag $nbt = null
	){
		if($item->isNull()){
			throw new \InvalidArgumentException("Trident must have a count of at least 1");
		}
		$this->setItem($item);
		parent::__construct($location, $shootingEntity, $nbt);
	}

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(0.25, 0.25); }

	protected function getInitialDragMultiplier() : float{ return 0.01; }

	protected function getInitialGravity() : float{ return 0.05; }

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);

		$this->spawnedInCreative = $nbt->getByte("isCreative", 0) === 1;

		$slot = $nbt->getInt("favoredSlot", -1);
		if($slot < 0 || $slot > 8){
			$slot = null;
		}
		$this->favoredSlot = $slot;
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setTag("Trident", $this->item->nbtSerialize());
		$nbt->setByte("isCreative", $this->spawnedInCreative ? 1 : 0);
		$nbt->setInt("favoredSlot", $this->favoredSlot ?? -1);
		return $nbt;
	}

	protected function onFirstUpdate(int $currentTick) : void{
		$owner = $this->getOwningEntity();
		$this->spawnedInCreative = $owner instanceof Player && $owner->isCreative();

		parent::onFirstUpdate($currentTick);
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		$loyaltyLevel = $this->item->getEnchantmentLevel(VanillaEnchantments::LOYALTY());
		if($loyaltyLevel > 0){
			if($this->blockHit !== null && !$this->isReturning){
				$this->startReturning();
			}
			if($this->isReturning){
				$owner = $this->getOwningEntity();
				$world = $this->getWorld();
				if($this->canReturn()){
					$this->setHasGravity(false);
					$this->setHasBlockCollision(false);
					$this->canCollide = false;

					$vectorDiff = $owner->getEyePos()->subtractVector($this->location);
					$this->setPosition($this->location->add(0, $vectorDiff->y * 0.015 * $loyaltyLevel, 0));
					$this->setMotion($this->motion->multiply(0.95)->addVector($vectorDiff->normalize()->multiply(0.05 * $loyaltyLevel)));
				}else{
					$world->dropItem($this->location, $this->item);
					$this->flagForDespawn();
				}
				$hasUpdate = true;
			}
		}

		return $hasUpdate;
	}

	protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : void{
		if(!$this->canCollide){
			return;
		}
		parent::onHitEntity($entityHit, $hitResult);
		$this->canCollide = false;
		$this->broadcastSound(new TridentHitSound());
	}

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult) : void{
		parent::onHitBlock($blockHit, $hitResult);
		$this->canCollide = true;
		$this->broadcastSound(new TridentHitGroundSound());
	}

	public function getMotionOnHit(?ProjectileHitEvent $event) : Vector3{
		if($event instanceof ProjectileHitEntityEvent){
			return new Vector3($this->motion->x * -0.01, $this->motion->y * -0.1, $this->motion->z * -0.01);
		}
		return parent::getMotionOnHit($event);
	}

	public function attack(EntityDamageEvent $source) : void{
		if($source->getCause() === EntityDamageEvent::CAUSE_VOID && $this->item->hasEnchantment(VanillaEnchantments::LOYALTY()) && $this->canReturn()){
			if(!$this->isReturning){
				$this->startReturning();
			}
			return;
		}
		parent::attack($source);
	}

	public function getItem() : TridentItem{
		return clone $this->item;
	}

	public function setItem(TridentItem $item) : void{
		if($item->isNull()){
			throw new \InvalidArgumentException("Trident must have a count of at least 1");
		}
		$this->item = clone $item;
		$this->networkPropertiesDirty = true;
	}

	public function getFavoredSlot() : ?int {
		return $this->favoredSlot;
	}

	public function setFavoredSlot(?int $slot) : void {
		if($slot !== null && ($slot < 0 || $slot > 8)){
			throw new \InvalidArgumentException("$slot is not a valid hotbar slot index (expected 0 - 8)");
		}
		$this->favoredSlot = $slot;
	}

	public function isReturning() : bool{
		return $this->isReturning;
	}

	private function startReturning() : void{
		$this->broadcastSound(new TridentReturnSound());
		$this->blockHit = null;
		$this->isReturning = true;
		$this->networkPropertiesDirty = true;
	}

	private function canReturn() : bool{
		$owner = $this->getOwningEntity();
		return $owner instanceof Player && $owner->canBeCollidedWith() && $owner->getWorld() === $this->getWorld();
	}

	public function canCollideWith(Entity $entity) : bool{
		return $this->canCollide && $entity->getId() !== $this->ownerId && parent::canCollideWith($entity);
	}

	public function onCollideWithPlayer(Player $player) : void{
		if($this->blockHit !== null || ($this->isReturning && $player->getId() === $this->ownerId)){
			$this->pickup($player);
		}
	}

	private function pickup(Player $player) : void{
		$shouldDespawn = false;

		$playerInventory = $player->getInventory();
		$ev = new EntityItemPickupEvent($player, $this, $this->getItem(), $player->getInventory());
		if($player->hasFiniteResources() && !$playerInventory->canAddItem($ev->getItem())){
			$ev->cancel();
		}
		if($this->spawnedInCreative){
			$ev->cancel();
			$shouldDespawn = true;
		}
		if($this->item->hasEnchantment(VanillaEnchantments::LOYALTY()) && $player->getId() !== $this->ownerId){
			$ev->cancel();
		}

		$ev->call();
		if(!$ev->isCancelled()){
			$inventory = $ev->getInventory();
			$item = $ev->getItem();
			if($inventory !== null){
				if($this->favoredSlot !== null && $inventory->slotExists($this->favoredSlot) && $inventory->isSlotEmpty($this->favoredSlot)){
					$inventory->setItem($this->favoredSlot, $item);
				}else{
					$ev->getInventory()->addItem($item);
				}
			}
			$shouldDespawn = true;
		}

		if($shouldDespawn){
			//even if the item was not actually picked up, the animation must be displayed.
			foreach($this->getViewers() as $viewer){
				$viewer->getNetworkSession()->onPlayerPickUpItem($player, $this);
			}
			$this->flagForDespawn();
		}
	}

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);

		$properties->setGenericFlag(EntityMetadataFlags::ENCHANTED, $this->item->hasEnchantments());
		$properties->setGenericFlag(EntityMetadataFlags::SHOW_TRIDENT_ROPE, $this->isReturning);
	}
}
