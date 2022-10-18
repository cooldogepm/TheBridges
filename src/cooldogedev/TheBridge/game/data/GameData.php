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

namespace cooldogedev\TheBridge\game\data;

use cooldogedev\TheBridge\constant\GameModes;
use cooldogedev\TheBridge\game\Game;

final class GameData
{
    public const GAME_DATA_NAME = "name";
    public const GAME_DATA_WORLD = "world";
    public const GAME_DATA_LOBBY = "lobby";
    public const GAME_DATA_COUNTDOWN = "countdown";
    public const GAME_DATA_DURATION = "duration";
    public const GAME_DATA_GRACE_DURATION = "grace_duration";
    public const GAME_DATA_END_DURATION = "end_duration";
    public const GAME_DATA_MODE = "mode";

    public const GAME_DATA_TEAMS = "teams";
    public const GAME_DATA_BRIDGE = "bridge";
    public const GAME_DATA_BRIDGE_MIN = "bridge_min";
    public const GAME_DATA_BRIDGE_MAX = "bridge_max";

    public function __construct(protected int $id, protected string $name, protected int $countdown, protected int $duration, protected int $graceDuration, protected int $endDuration, protected array $teams, protected array $bridge, protected string $mode)
    {
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getGraceDuration(): int
    {
        return $this->graceDuration;
    }

    public function getTeams(): array
    {
        return $this->teams;
    }

    public function getBridge(): array
    {
        return $this->bridge;
    }

    public function getLobby(): string
    {
        return Game::GAME_LOBBY_IDENTIFIER . $this->id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEndDuration(): int
    {
        return $this->endDuration;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMinPlayersPerTeam(): int
    {
        return $this->mode > 1 ? (int)floor($this->getMaxPlayers() / 2) : 1;
    }

    public function getMaxPlayers(): int
    {
        return $this->getPlayersPerTeam() * count($this->teams);
    }

    public function getPlayersPerTeam(): int
    {
        return match (ucfirst(strtolower($this->mode))) {
            GameModes::GAME_MODE_DOUBLES => 2,
            GameModes::GAME_MODE_TRIOS => 3,
            GameModes::GAME_MODE_SQUADS => 4,
            default => 1,
        };
    }

    public function getCountdown(): int
    {
        return $this->countdown;
    }

    public function getWorld(): string
    {
        return Game::GAME_WORLD_IDENTIFIER . $this->id;
    }

    public function matches(array $data): bool
    {
        $map = $data["map"] ?? null;
        $mode = $data["mode"] ?? null;

        if ($mode === null || $map === null) {
            return true;
        }

        return strtolower($this->mode) === strtolower($mode) && strtolower($this->name) === strtolower($map);
    }
}
