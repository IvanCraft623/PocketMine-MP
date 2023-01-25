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
use pocketmine\data\bedrock\SuspiciousStewTypeIdMap;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;
use pocketmine\world\loot\entry\function\types\EnchantRandomlyFunction;
use pocketmine\world\loot\entry\function\types\RandomDyeFunction;
use pocketmine\world\loot\entry\function\types\SetCountFunction;
use pocketmine\world\loot\entry\function\types\SetCustomNameFunction;
use pocketmine\world\loot\entry\function\types\SetDamageFunction;
use pocketmine\world\loot\entry\function\types\SetMetaFunction;
use pocketmine\world\loot\entry\function\types\SetSuspiciousStewTypeFunction;
use function count;
use function is_array;
use function is_numeric;
use function is_string;
use function reset;
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
		$this->register(EnchantRandomlyFunction::class, function(array $data) : EnchantRandomlyFunction{
			if(isset($data["treasure"])){
				$treasure = (bool) $data["treasure"];
			}else{
				$treasure = false;
			}
			return new EnchantRandomlyFunction($treasure);
		}, ["enchant_randomly"]);

		$this->register(RandomDyeFunction::class, function(array $data) : RandomDyeFunction{
			return new RandomDyeFunction();
		}, ["random_dye"]);

		$this->register(SetCountFunction::class, function(array $data) : SetCountFunction{
			if(!isset($data["count"])){
				throw new SavedDataLoadingException("Key \"count\" doesn't exists");
			}
			$count = $data["count"];
			if(is_numeric($count)){
				$min = $max = (int) $count;
			}elseif(is_array($count)){
				if(!isset($count["min"]) || !is_numeric($count["min"])){
					throw new SavedDataLoadingException("Value \"min\" isn't numeric or doesn't exists");
				}
				if(!isset($count["max"]) || !is_numeric($count["max"])){
					throw new SavedDataLoadingException("Value \"max\" isn't numeric or doesn't exists");
				}
				$min = (int) $count["min"];
				$max = (int) $count["max"];
			}else{
				throw new SavedDataLoadingException("Min and max values not found");
			}
			if($min < 0){
				throw new SavedDataLoadingException("Min cannot be less than 0");
			}
			if($min > $max){
				throw new SavedDataLoadingException("Min is larger that max");
			}
			return new SetCountFunction($min, $max);
		}, ["set_count"]);

		$this->register(SetCustomNameFunction::class, function(array $data) : SetCustomNameFunction{
			$name = $data["name"] ?? null;
			if(!is_string($name)){
				throw new SavedDataLoadingException("Name is not a string or key doesn't exists");
			}
			return new SetCustomNameFunction($name);
		}, ["set_name"]);

		$this->register(SetDamageFunction::class, function(array $data) : SetDamageFunction{
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
			return new SetDamageFunction($min, $max);
		}, ["set_damage"]);

		$this->register(SetMetaFunction::class, function(array $data) : SetMetaFunction{
			$meta = $data["data"] ?? $data["values"] ?? throw new SavedDataLoadingException("Expected keys \"data\" or \"values\"");
			if(is_numeric($meta)){
				$min = $max = (int) $meta;
			}elseif(is_array($meta)){
				if(!isset($meta["min"]) || !is_numeric($meta["min"])){
					throw new SavedDataLoadingException("Value \"min\" isn't numeric or doesn't exists");
				}
				if(!isset($meta["max"]) || !is_numeric($meta["max"])){
					throw new SavedDataLoadingException("Value \"max\" isn't numeric or doesn't exists");
				}
				$min = (int) $meta["min"];
				$max = (int) $meta["max"];
			}else{
				throw new SavedDataLoadingException("Min and max values not found");
			}
			if($min < 0){
				throw new SavedDataLoadingException("Min cannot be less than 0");
			}
			if($min > $max){
				throw new SavedDataLoadingException("Min is larger that max");
			}
			return new SetMetaFunction($min, $max);
		}, ["set_data", "random_aux_value"]);

		$this->register(SetSuspiciousStewTypeFunction::class, function(array $data) : SetSuspiciousStewTypeFunction{
			if(!isset($data["effects"]) || !is_array($data["effects"])){
				throw new SavedDataLoadingException("\"effects\" isn't an array or doesn't exists");
			}

			$types = [];
			foreach($data["effects"] as $typeData){
				if(!isset($typeData["id"]) || !is_numeric($typeData["id"])){
					throw new SavedDataLoadingException("\"id\" isn't numeric or doesn't exists");
				}
				$id = (int) $typeData["id"];
				$types[] = SuspiciousStewTypeIdMap::getInstance()->fromId($id) ?? throw new SavedDataLoadingException("Unknown suspicious stew type ID $id");
			}
			if(count($types) === 0){
				throw new SavedDataLoadingException("No suspicious stew types found");
			}
			return new SetSuspiciousStewTypeFunction($types);
		}, ["set_stew_effect"]);
	}

	/**
	 * Registers an entry function type into the index.
	 *
	 * @param string   $className Class that extends EntryFunction
	 * @param string[] $saveNames An array of save names which this entity might be saved under.
	 * @phpstan-param class-string<EntryFunction> $className
	 * @phpstan-param list<string> $saveNames
	 * @phpstan-param \Closure(array<string, mixed> $arguments) : EntryFunction $creationFunc
	 *
	 * NOTE: The first save name in the $saveNames array will be used when saving the function.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function register(string $className, \Closure $creationFunc, array $saveNames) : void{
		if(count($saveNames) === 0){
			throw new \InvalidArgumentException("At least one save name must be provided");
		}
		Utils::testValidInstance($className, EntryFunction::class);
		Utils::validateCallableSignature(new CallbackType(
			new ReturnType(EntryFunction::class),
			new ParameterType("arguments", BuiltInTypes::ARRAY)
		), $creationFunc);

		foreach($saveNames as $name){
			$this->creationFuncs[$name] = $creationFunc;
		}
		$this->saveNames[$className] = reset($saveNames);
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
