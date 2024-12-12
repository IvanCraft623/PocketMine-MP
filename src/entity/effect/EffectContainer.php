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

namespace pocketmine\entity\effect;

use DaveRandom\CallbackValidator\BuiltInTypes;
use DaveRandom\CallbackValidator\CallbackType;
use DaveRandom\CallbackValidator\ParameterType;
use DaveRandom\CallbackValidator\ReturnType;
use pocketmine\color\Color;
use pocketmine\utils\ObjectSet;
use pocketmine\utils\Utils;
use function abs;
use function count;
use function spl_object_id;

class EffectContainer{

	/** @var EffectInstance[] */
	protected array $effects = [];

	/**
	 * @var \Closure[]|ObjectSet
	 * @phpstan-var ObjectSet<\Closure(EffectInstance, bool $replacesOldEffect) : void>
	 */
	protected ObjectSet $effectAddHooks;

	/**
	 * @var \Closure[]|ObjectSet
	 * @phpstan-var ObjectSet<\Closure(EffectInstance) : void>
	 */
	protected ObjectSet $effectRemoveHooks;

	protected Color $bubbleColor;

	protected bool $onlyAmbientEffects = false;

	/**
	 * Validates whether an effect will be used for bubbles color calculation.
	 *
	 * @phpstan-var \Closure(EffectInstance) : bool
	 */
	protected \Closure $effectValidatorForBubbles;

	public function __construct(){
		$this->bubbleColor = new Color(0, 0, 0, 0);
		$this->effectAddHooks = new ObjectSet();
		$this->effectRemoveHooks = new ObjectSet();

		$this->setEffectFilterForBubbles(function(EffectInstance $effect) : bool{
			return $effect->isVisible() && $effect->getType()->hasBubbles();
		});
	}

	/**
	 * Returns an array of Effects currently active.
	 * @return EffectInstance[]
	 */
	public function all() : array{
		return $this->effects;
	}

	/**
	 * Removes all effects.
	 */
	public function clear() : void{
		foreach($this->effects as $effect){
			$this->remove($effect->getType());
		}
	}

	/**
	 * Removes the effect with the specified ID.
	 */
	public function remove(Effect $effectType) : void{
		$index = spl_object_id($effectType);
		if(isset($this->effects[$index])){
			$effect = $this->effects[$index];

			unset($this->effects[$index]);
			foreach($this->effectRemoveHooks as $hook){
				$hook($effect);
			}

			$this->recalculateEffectColor();
		}
	}

	/**
	 * Returns the effect instance active with the specified ID, or null if does not have the
	 * effect.
	 */
	public function get(Effect $effect) : ?EffectInstance{
		return $this->effects[spl_object_id($effect)] ?? null;
	}

	/**
	 * Returns whether the specified effect is active.
	 */
	public function has(Effect $effect) : bool{
		return isset($this->effects[spl_object_id($effect)]);
	}

	/**
	 * In the following cases it will return true:
	 * - if the effect type is not already applied
	 * - if an existing effect of the same type can be replaced (due to shorter duration or lower level)
	 */
	public function canAdd(EffectInstance $effect) : bool{
		$index = spl_object_id($effect->getType());
		if(isset($this->effects[$index])){
			$oldEffect = $this->effects[$index];
			if(
				abs($effect->getAmplifier()) < $oldEffect->getAmplifier()
				|| (abs($effect->getAmplifier()) === abs($oldEffect->getAmplifier()) && $effect->getDuration() < $oldEffect->getDuration())
			){
				return false;
			}
		}
		return true;
	}

	/**
	 * Adds an effect.
	 * If {@link EffectContainer::canAdd()} conditions are met.
	 *
	 * @return bool whether the effect has been successfully applied.
	 */
	public function add(EffectInstance $effect) : bool{
		if($this->canAdd($effect)){
			$index = spl_object_id($effect->getType());
			$replacesOldEffect = isset($this->effects[$index]);

			$this->effects[$index] = $effect;
			foreach($this->effectAddHooks as $hook){
				$hook($effect, $replacesOldEffect);
			}

			$this->recalculateEffectColor();
			return true;
		}

		return false;
	}

	/**
	 * Sets the filter that determines which effects will be displayed in the bubbles.
	 *
	 * @phpstan-param \Closure(EffectInstance) : bool $effectValidator
	 */
	public function setEffectFilterForBubbles(\Closure $effectValidator) : void{
		Utils::validateCallableSignature(new CallbackType(new ReturnType(BuiltInTypes::BOOL), new ParameterType("effect", EffectInstance::class)), $effectValidator);
		$this->effectValidatorForBubbles = $effectValidator;
	}

	/**
	 * Recalculates the potion bubbles colour based on the active effects.
	 */
	protected function recalculateEffectColor() : void{
		/** @var Color[] $colors */
		$colors = [];
		$ambient = true;
		foreach($this->effects as $effect){
			if(($this->effectValidatorForBubbles)($effect)){
				$level = $effect->getEffectLevel();
				$color = $effect->getColor();
				for($i = 0; $i < $level; ++$i){
					$colors[] = $color;
				}

				if(!$effect->isAmbient()){
					$ambient = false;
				}
			}
		}

		if(count($colors) > 0){
			$this->bubbleColor = Color::mix(...$colors);
			$this->onlyAmbientEffects = $ambient;
		}else{
			$this->bubbleColor = new Color(0, 0, 0, 0);
			$this->onlyAmbientEffects = false;
		}
	}

	public function getBubbleColor() : Color{
		return $this->bubbleColor;
	}

	public function hasOnlyAmbientEffects() : bool{
		return $this->onlyAmbientEffects;
	}

	/**
	 * @return \Closure[]|ObjectSet
	 * @phpstan-return ObjectSet<\Closure(EffectInstance, bool $replacesOldEffect) : void>
	 */
	public function getEffectAddHooks() : ObjectSet{
		return $this->effectAddHooks;
	}

	/**
	 * @return \Closure[]|ObjectSet
	 * @phpstan-return ObjectSet<\Closure(EffectInstance) : void>
	 */
	public function getEffectRemoveHooks() : ObjectSet{
		return $this->effectRemoveHooks;
	}
}
