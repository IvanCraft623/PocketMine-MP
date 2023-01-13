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

namespace pocketmine\world\loot\entry\function;

use DaveRandom\CallbackValidator\BuiltInTypes;
use DaveRandom\CallbackValidator\CallbackType;
use DaveRandom\CallbackValidator\ParameterType;
use DaveRandom\CallbackValidator\ReturnType;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;
use pocketmine\world\loot\entry\function\types\EnchantRandomly;
use pocketmine\world\loot\entry\function\types\RandomDye;
use pocketmine\world\loot\entry\function\types\SetCount;
use pocketmine\world\loot\entry\function\types\SetCustomName;
use pocketmine\world\loot\entry\function\types\SetDamage;
use pocketmine\world\loot\entry\function\types\SetMeta;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function str_replace;
use function strtolower;
use function trim;

final class EntryFunctionFactory{
	use SingletonTrait;

	/**
	 * @var \Closure[] save ID => creator function
	 * @phpstan-var array<string, \Closure(array<string, mixed>) : EntryFunction>
	 */
	private array $creationFuncs = [];

	/**
	 * @var string[]
	 * @phpstan-var array<class-string<EntryFunction>, string>
	 */
	private array $saveNames = [];

	public function __construct() {
		$this->register(EnchantRandomly::class, function(array $data) : EnchantRandomly{
			if(isset($data["treasure"])){
				$treasure = (bool) $data["treasure"];
			}else{
				$treasure = false;
			}
			return new EnchantRandomly($treasure);
		}, "enchant_randomly");

		$this->register(RandomDye::class, function(array $data) : RandomDye{
			return new RandomDye();
		}, "random_dye");

		$this->register(SetCount::class, function(array $data) : SetCount{
			if(!isset($data["count"])){
				throw new SavedDataLoadingException("Key \"count\" doesn't exists");
			}
			$count = $data["count"];
			if(is_int($count)){
				$min = $max = $count;
			}elseif(is_array($count)){
				if(!isset($count["min"]) || !is_int($count["min"])){
					throw new SavedDataLoadingException("Value \"min\" isn't an int or doesn't exists");
				}
				if(!isset($count["max"]) || !is_int($count["max"])){
					throw new SavedDataLoadingException("Value \"max\" isn't an int or doesn't exists");
				}
				$min = $count["min"];
				$max = $count["max"];
			}else{
				throw new SavedDataLoadingException("Min and max values not found");
			}
			if($min < 0){
				throw new SavedDataLoadingException("Min cannot be less than 0");
			}
			if($min > $max){
				throw new SavedDataLoadingException("Min is larger that max");
			}
			return new SetCount($min, $max);
		}, "set_count");

		$this->register(SetCustomName::class, function(array $data) : SetCustomName{
			$name = $data["name"] ?? null;
			if(!is_string($name)){
				throw new SavedDataLoadingException("Name is not a string or key doesn't exists");
			}
			return new SetCustomName($name);
		}, "set_name");

		$this->register(SetDamage::class, function(array $data) : SetDamage{
			if(!isset($data["damage"])){
				throw new SavedDataLoadingException("Key \"damage\" doesn't exists");
			}
			$damage = $data["damage"];
			if(is_numeric($damage)){
				$min = $max = (float) $damage;
			}elseif(is_array($damage)){
				if(!isset($damage["min"]) || !is_numeric($damage["min"])){
					throw new SavedDataLoadingException("Value \"min\" isn't numeric or doesn't exists");
				}
				if(!isset($damage["max"]) || !is_numeric($damage["max"])){
					throw new SavedDataLoadingException("Value \"max\" isn't numeric or doesn't exists");
				}
				$min = (float) $damage["min"];
				$max = (float) $damage["max"];
			}else{
				throw new SavedDataLoadingException("Min and max values not found");
			}
			if($max < 0 || $max > 1){
				throw new SavedDataLoadingException("Max must be between 0.0 and 1.0");
			}
			if($min > $max){
				throw new SavedDataLoadingException("Min is larger that max");
			}
			return new SetDamage($min, $max);
		}, "set_damage");

		$this->register(SetMeta::class, function(array $data) : SetMeta{
			if(!isset($data["data"])){
				throw new SavedDataLoadingException("Key \"data\" doesn't exists");
			}
			$meta = $data["data"];
			if(is_int($meta)){
				$min = $max = $meta;
			}elseif(is_array($meta)){
				if(!isset($meta["min"]) || !is_int($meta["min"])){
					throw new SavedDataLoadingException("Value \"min\" isn't an int or doesn't exists");
				}
				if(!isset($meta["max"]) || !is_int($meta["max"])){
					throw new SavedDataLoadingException("Value \"max\" isn't an int or doesn't exists");
				}
				$min = $meta["min"];
				$max = $meta["max"];
			}else{
				throw new SavedDataLoadingException("Min and max values not found");
			}
			if($min < 0){
				throw new SavedDataLoadingException("Min cannot be less than 0");
			}
			if($min > $max){
				throw new SavedDataLoadingException("Min is larger that max");
			}
			return new SetMeta($min, $max);
		}, "set_data");
	}

	/**
	 * Registers an entry function type into the index.
	 *
	 * @param string $className Class that extends EntryFunction
	 * @phpstan-param class-string<EntryFunction> $className
	 * @phpstan-param \Closure(array<string, mixed> $arguments) : EntryFunction $creationFunc
	 *
	 * @throws \InvalidArgumentException
	 */
	public function register(string $className, \Closure $creationFunc, string $saveName) : void{
		Utils::testValidInstance($className, EntryFunction::class);
		Utils::validateCallableSignature(new CallbackType(
			new ReturnType(EntryFunction::class),
			new ParameterType("arguments", BuiltInTypes::ARRAY)
		), $creationFunc);

		$this->creationFuncs[$saveName] = $creationFunc;
		$this->saveNames[$className] = $saveName;
	}

	/**
	 * Creates an entry function from data stored on a chunk.
	 *
	 * @param mixed[] $data
	 * @phpstan-param array{
	 * 	function: string,
	 * 	...
	 * } $data
	 *
	 * @throws SavedDataLoadingException
	 * @internal
	 */
	public function createFromData(array $data) : ?EntryFunction{
		$func = $this->creationFuncs[$this->reprocess($data["function"])] ?? null;
		if($func === null){
			return null;
		}
		unset($data["function"]);

		/** @var EntryFunction $function */
		$function = $func($data);

		return $function;
	}

	/**
	 * @phpstan-param class-string<EntryFunction> $class
	 */
	public function getSaveId(string $class) : string{
		if(isset($this->saveNames[$class])){
			return $this->saveNames[$class];
		}
		throw new \InvalidArgumentException("EntryFunction $class is not registered");
	}

	protected function reprocess(string $input) : string{
		return strtolower(str_replace([" ", "minecraft:"], ["_", ""], trim($input)));
	}
}
