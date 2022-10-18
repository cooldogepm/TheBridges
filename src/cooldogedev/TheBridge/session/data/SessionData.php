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

namespace cooldogedev\TheBridge\session\data;

use cooldogedev\TheBridge\game\Game;
use cooldogedev\TheBridge\game\team\Team;
use cooldogedev\TheBridge\session\Session;

trait SessionData
{
    protected ?Game $game = null;
    protected ?Team $team = null;
    protected int $state = Session::PLAYER_STATE_DEFAULT;

    protected int $gameKills = 0;
    protected int $goals = 0;

    protected bool $queued = false;

    protected ?string $prevDisplayName = null;
    protected ?string $prevNameTag = null;

    protected ?Session $lastDamager = null;
    protected ?int $lastDamageTime = null;

    public function getLastDamager(): ?Session
    {
        return $this->lastDamager;
    }

    public function setLastDamager(?Session $lastDamager): void
    {
        $this->lastDamager = $lastDamager;
    }

    public function getPrevNameTag(): ?string
    {
        return $this->prevNameTag;
    }

    public function setPrevNameTag(?string $prevNameTag): void
    {
        $this->prevNameTag = $prevNameTag;
    }

    public function getPrevDisplayName(): ?string
    {
        return $this->prevDisplayName;
    }

    public function setPrevDisplayName(?string $prevDisplayName): void
    {
        $this->prevDisplayName = $prevDisplayName;
    }

    public function getLastDamageTime(): ?int
    {
        return $this->lastDamageTime;
    }

    public function setLastDamageTime(?int $lastDamageTime): void
    {
        $this->lastDamageTime = $lastDamageTime;
    }

    public function addGoal(): void
    {
        $this->goals++;
    }

    public function addGameKill(): void
    {
        $this->gameKills++;
    }

    public function getGoals(): int
    {
        return $this->goals;
    }

    public function setGoals(int $goals): void
    {
        $this->goals = $goals;
    }

    public function getGameKills(): int
    {
        return $this->gameKills;
    }

    public function setGameKills(int $gameKills): void
    {
        $this->gameKills = $gameKills;
    }

    public function inTeam(): bool
    {
        return $this->team !== null;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): void
    {
        $this->team = $team;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state): void
    {
        $this->state = $state;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): void
    {
        $this->game = $game;
    }

    public function isQueued(): bool
    {
        return $this->queued;
    }

    public function setQueued(bool $queued): void
    {
        $this->queued = $queued;
    }

    public function inGame(): bool
    {
        return $this->game !== null;
    }
}
