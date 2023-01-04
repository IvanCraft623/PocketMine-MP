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

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\particle\SmokeParticle;
use pocketmine\world\sound\CameraTakePictureSound;
use function atan2;
use function rad2deg;

class Camera extends Entity{

	private const TAG_HEALTH = "Health"; //TAG_Short

	protected int $fuse = 80;

	public static function getNetworkTypeId() : string{ return EntityIds::TRIPOD_CAMERA; }

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(1.8, 0.75); }

	protected function getInitialDragMultiplier() : float{ return 0.02; }

	protected function getInitialGravity() : float{ return 0.08; }

	protected function initEntity(CompoundTag $nbt) : void{
		$this->setMaxHealth(4);
		$this->setHealth($nbt->getShort(self::TAG_HEALTH, (int) $this->getHealth()));
		parent::initEntity($nbt);
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setShort(self::TAG_HEALTH, (int) $this->getHealth());

		return $nbt;
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		$target = $this->getTargetEntity();
		$world = $this->getWorld();
		if($target !== null && $target->getWorld() === $world){
			$this->fuse -= $tickDiff;
			$this->networkPropertiesDirty = true;

			$diff = $target->getLocation()->subtractVector($this->location);
			$yaw = rad2deg(atan2($diff->z, $diff->x)) - 90;
			if($yaw < 0){
				$yaw += 360;
			}
			$this->setRotation($yaw, 0);

			if ($this->fuse % 2 === 0) {
				$this->location->getWorld()->addParticle($this->location->addVector($this->getDirectionVector()->multiply(-(4 / 16) * $this->getScale())->add(0, $this->getSize()->getHeight(), 0)), new SmokeParticle());
			}

			if($this->fuse <= 0){
				$this->flagForDespawn();
				$world->addSound($target->getLocation(), new CameraTakePictureSound());
			}

			$hasUpdate = true;
		}else{
			$this->fuse = 80;
		}

		return $hasUpdate;
	}

	public function attack(EntityDamageEvent $source) : void{
		//It is only damaged if caused by a player
		if($source->getCause() !== EntityDamageEvent::CAUSE_VOID && !($source instanceof EntityDamageByEntityEvent && $source->getDamager() instanceof Player)){
			$source->cancel();
		}

		parent::attack($source);

		if(!$source->isCancelled()){
			$this->flagForDespawn();
		}
	}

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
		if($this->getTargetEntity() === null){
			$this->setTargetEntity($player);
			return true;
		}
		return parent::onInteract($player, $clickPos);
	}

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);

		$properties->setInt(EntityMetadataProperties::FUSE_LENGTH, $this->fuse);
	}
}
