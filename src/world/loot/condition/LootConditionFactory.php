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

namespace pocketmine\world\loot\condition;

use DaveRandom\CallbackValidator\BuiltInTypes;
use DaveRandom\CallbackValidator\CallbackType;
use DaveRandom\CallbackValidator\ParameterType;
use DaveRandom\CallbackValidator\ReturnType;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;
use pocketmine\world\loot\condition\types\KilledByPlayerCondition;
use pocketmine\world\loot\condition\types\KilledByPlayerOrChildCondition;
use pocketmine\world\loot\condition\types\RandomChanceCondition;
use function is_float;
use function str_replace;
use function strtolower;
use function trim;

final class LootConditionFactory{
	use SingletonTrait;

	/**
	 * @var \Closure[] save ID => creator function
	 * @phpstan-var array<string, \Closure(array<string, mixed>) : LootCondition>
	 */
	private array $creationFuncs = [];

	/**
	 * @var string[]
	 * @phpstan-var array<class-string<LootCondition>, string>
	 */
	private array $saveNames = [];

	public function __construct() {
		$this->register(KilledByPlayerCondition::class, function(array $data) : KilledByPlayerCondition{
			return new KilledByPlayerCondition();
		}, "killed_by_player");

		$this->register(KilledByPlayerOrChildCondition::class, function(array $data) : KilledByPlayerOrChildCondition{
			return new KilledByPlayerOrChildCondition();
		}, "killed_by_player_or_pets");

		$this->register(RandomChanceCondition::class, function(array $data) : RandomChanceCondition{
			if(!isset($data["chance"])){
				throw new SavedDataLoadingException("Key \"chance\" doesn't exists");
			}
			$chance = $data["chance"];
			if(!is_float($chance)){
				throw new SavedDataLoadingException("Expected value of type float in key \"chance\"");
			}
			return new RandomChanceCondition($chance);
		}, "random_chance");
	}

	/**
	 * Registers a loot condition type into the index.
	 *
	 * @param string $className Class that extends LootCondition
	 * @phpstan-param class-string<LootCondition> $className
	 * @phpstan-param \Closure(array<string, mixed> $arguments) : LootCondition $creationFunc
	 *
	 * @throws \InvalidArgumentException
	 */
	public function register(string $className, \Closure $creationFunc, string $saveName) : void{
		Utils::testValidInstance($className, LootCondition::class);
		Utils::validateCallableSignature(new CallbackType(
			new ReturnType(LootCondition::class),
			new ParameterType("arguments", BuiltInTypes::ARRAY)
		), $creationFunc);

		$this->creationFuncs[$saveName] = $creationFunc;
		$this->saveNames[$className] = $saveName;
	}

	/**
	 * Creates an loot condition from data stored on a chunk.
	 *
	 * @param mixed[] $data
	 * @phpstan-param array{
	 * 	condition: string,
	 * 	...
	 * } $data
	 *
	 * @throws SavedDataLoadingException
	 * @internal
	 */
	public function createFromData(array $data) : ?LootCondition{
		$func = $this->creationFuncs[$this->reprocess($data["condition"])] ?? null;
		if($func === null){
			return null;
		}
		unset($data["condition"]);

		/** @var LootCondition $condition */
		$condition = $func($data);

		return $condition;
	}

	/**
	 * @phpstan-param class-string<LootCondition> $class
	 */
	public function getSaveId(string $class) : string{
		if(isset($this->saveNames[$class])){
			return $this->saveNames[$class];
		}
		throw new \InvalidArgumentException("LootCondition $class is not registered");
	}

	protected function reprocess(string $input) : string{
		return strtolower(str_replace([" ", "minecraft:"], ["_", ""], trim($input)));
	}
}
