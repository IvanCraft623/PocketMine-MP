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

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\entity\object\Camera as CameraEntity;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\BlockPlaceSound;
use pocketmine\world\sound\CameraTakePictureSound;

class Camera extends Item implements Releasable{

	public function getBlock(?int $clickedFace = null) : Block{
		return VanillaBlocks::CAMERA();
	}

	public function canStartUsingItem(Player $player) : bool{
		return true;
	}

	public function onReleaseUsing(Player $player, array &$returnedItems) : ItemUseResult{
		//Picture is handled client-side

		$pos = $player->getLocation();
		$pos->getWorld()->addSound($pos, new CameraTakePictureSound());

		return ItemUseResult::SUCCESS();
	}

	public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, array &$returnedItems) : ItemUseResult{
		$world = $player->getWorld();
		$pos = Location::fromObject($blockReplace->getPosition()->add(0.5, 0, 0.5), $world, ($player->getLocation()->getYaw() + 180) % 360);
		$entity = new CameraEntity($pos);

		if($this->hasCustomName()){
			$entity->setNameTag($this->getCustomName());
		}

		$entity->spawnToAll();
		$world->addSound($pos, new BlockPlaceSound($this->getBlock()));

		return ItemUseResult::SUCCESS();
	}
}
