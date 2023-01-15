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

namespace pocketmine\world\loot;

use pocketmine\utils\SingletonTrait;
use function str_replace;
use function strtolower;
use function trim;

final class LootTableFactory{
	use SingletonTrait;

	private const SAVE_DIR = "loot_tables/";
	private const SAVE_EXTENSION = ".json";

	/**
	 * @var LootTable[]
	 * @phpstan-var array<string, LootTable>
	 */
	private array $lootTables = [];

	/**
	 * @var string[]
	 * @phpstan-var array<class-string<LootTable>, string>
	 */
	private array $names = [];

	public function __construct() {
		//TODO: register vanilla loot tables
	}

	/**
	 * Registers a loot table type into the index.
	 *
	 * @throws \RuntimeException
	 */
	public function register(LootTable $table, string $name, bool $override = false) : void{
		if(!$override && isset($this->names[$name])){
			throw new \RuntimeException("Trying to overwrite an already registered name");
		}

		$this->lootTables[$name] = $table;
		$this->names[$table::class] = $name;
	}

	public function get(string $name) : ?LootTable{
		return $this->lootTables[$this->reprocess($name)] ?? null;
	}

	/**
	 * @return LootTable[]
	 * @phpstan-return array<string, LootTable>
	 */
	public function getAll() : array{
		return $this->lootTables;
	}

	/**
	 * @phpstan-param class-string<LootTable> $class
	 */
	public function getSaveName(string $class) : string{
		if(isset($this->names[$class])){
			return self::SAVE_DIR . $this->names[$class] . self::SAVE_EXTENSION;
		}
		throw new \InvalidArgumentException("LootTable $class is not registered");
	}

	protected function reprocess(string $input) : string{
		return strtolower(str_replace([" ", self::SAVE_DIR, self::SAVE_EXTENSION], ["_", "", ""], trim($input)));
	}
}
