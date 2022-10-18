<?php

/**
 * Copyright (c) 2022 cooldogedev
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @auto-license
 */

declare(strict_types=1);

namespace cooldogedev\TheBridge\game;

use cooldogedev\TheBridge\game\data\GameData;
use cooldogedev\TheBridge\game\handler\RoundHandler;
use cooldogedev\TheBridge\TheBridge;

final class GameManager
{
    protected int $generatedGames = 0;
    protected array $games = [];
    protected array $maps = [];

    public function __construct(protected TheBridge $plugin)
    {
        $this->loadMaps();
    }

    protected function loadMaps(): void
    {
        foreach (scandir($this->plugin->getDataFolder() . "maps" . DIRECTORY_SEPARATOR) as $map) {
            if ($map === "." || $map === ".." || is_file($map)) {
                continue;
            }
            $data = json_decode(file_get_contents($this->plugin->getDataFolder() . "maps" . DIRECTORY_SEPARATOR . $map . DIRECTORY_SEPARATOR . "data.json"), true);
            $this->addMap(strtolower($data["name"]), $data);
        }
    }

    public function addMap(string $map, array $data, bool $overwrite = false): bool
    {
        if (!$overwrite && $this->isMapExists($map)) {
            return false;
        }

        if ($overwrite) {
            file_put_contents($this->getPlugin()->getDataFolder() . "maps" . DIRECTORY_SEPARATOR . $map . DIRECTORY_SEPARATOR . "data.json", json_encode($data));
        }

        $this->maps[strtolower($map)] = $data;
        return true;
    }

    public function isMapExists(string $map): bool
    {
        return isset($this->maps[strtolower($map)]);
    }

    public function getPlugin(): TheBridge
    {
        return $this->plugin;
    }

    public function handleMovementTick(): void
    {
        foreach ($this->games as $game) {
            if (!$game->getHandler() instanceof RoundHandler) {
                continue;
            }

            foreach ($game->getPlayerManager()->getSessions() as $session) {
                $team = $game->getTeamManager()->getTeamByPosition($session->getPlayer()->getPosition()->floor());

                if ($team !== null) {
                    $game->getHandler()->checkScore($session, $team);
                }
            }
        }
    }

    public function removeMap(string $map): bool
    {
        if (!$this->isMapExists($map)) {
            return false;
        }
        unset($this->maps[strtolower($map)]);
        return true;
    }

    public function queueToGame(array $queues, array $data = []): ?Game
    {
        $availableGames = [];

        foreach ($this->games as $index => $game) {
            if (
                !$game->getData()->matches($data) ||
                !$game->isFree() ||
                count($queues) > $game->getPlayerManager()->getEmptySlots()
            ) {
                continue;
            }
            $availableGames[$index] = $game;
        }

        if (count($availableGames) >= 1) {
            $game = $this->getGames()[array_rand($availableGames)];

            foreach ($queues as $queue) {
                $game->getQueueManager()->add($queue, $data);
            }

            return $game;
        }

        $game = $this->generateGame($data);

        foreach ($queues as $queue) {
            $game->getQueueManager()->add($queue, $data);
        }

        return $game;
    }

    /**
     * @return Game[]
     */
    public function getGames(): array
    {
        return $this->games;
    }

    public function generateGame(array $data): Game
    {
        if (!isset($data["map"]) || !$this->isMapExists($data["map"])) {
            $data["map"] = array_rand($this->maps);
        }

        $mapData = $this->getMap($data["map"]);

        $name = $mapData[GameData::GAME_DATA_NAME];
        $countdown = (int)$mapData[GameData::GAME_DATA_COUNTDOWN];
        $duration = (int)$mapData[GameData::GAME_DATA_DURATION];
        $graceDuration = (int)$mapData[GameData::GAME_DATA_GRACE_DURATION];
        $endDuration = (int)$mapData[GameData::GAME_DATA_END_DURATION];
        $mode = (string)$mapData[GameData::GAME_DATA_MODE];
        $teams = (array)$mapData[GameData::GAME_DATA_TEAMS];
        $bridge = (array)$mapData[GameData::GAME_DATA_BRIDGE];

        $game = new Game($this->plugin, new GameData($this->generatedGames, $name, $countdown, $duration, $graceDuration, $endDuration, $teams, $bridge, $mode));

        $this->games[$this->generatedGames] = $game;

        $this->generatedGames++;

        return $game;
    }

    public function getMap(string $name): ?array
    {
        return $this->maps[strtolower($name)] ?? null;
    }

    public function getMaps(): array
    {
        return $this->maps;
    }

    public function removeGame(int $id): bool
    {
        if (!$this->isGameExists($id)) {
            return false;
        }

        unset($this->games[$id]);
        return true;
    }

    public function isGameExists(int $id): bool
    {
        return isset($this->games[$id]);
    }

    public function handleTick(): void
    {
        foreach ($this->getGames() as $game) {
            $game->tickGame();
        }
    }
}
