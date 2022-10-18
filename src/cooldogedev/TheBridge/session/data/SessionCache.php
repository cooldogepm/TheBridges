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

trait SessionCache
{
    protected int $kills = 0;
    protected int $deaths = 0;
    protected int $wins = 0;
    protected int $winStreak = 0;
    protected int $losses = 0;
    protected bool $updated = false;
    protected bool $unregistered = false;

    public function getKills(): int
    {
        return $this->kills;
    }

    public function setKills(int $kills): void
    {
        if (!$this->updated) {
            $this->updated = true;
        }
        $this->kills = $kills;
    }

    public function getDeaths(): int
    {
        return $this->deaths;
    }

    public function setDeaths(int $deaths): void
    {
        if (!$this->updated) {
            $this->updated = true;
        }
        $this->deaths = $deaths;
    }

    public function isUnregistered(): bool
    {
        return $this->unregistered;
    }

    public function isUpdated(): bool
    {
        return $this->updated;
    }

    public function addWin(int $wins): void
    {
        $this->setWins($this->wins + $wins);
    }

    public function addLoss(int $losses): void
    {
        $this->setLosses($this->losses + $losses);
    }

    public function addKill(int $kills): void
    {
        $this->setKills($this->kills + $kills);
    }

    public function addDeath(int $deaths): void
    {
        $this->setDeaths($this->deaths + $deaths);
    }

    public function addWinStreak(int $winStreak): void
    {
        $this->setWinStreak($this->winStreak + $winStreak);
    }

    public function toArray(): array
    {
        return [
            "wins" => $this->wins,
            "win_streak" => $this->winStreak,
            "losses" => $this->losses,
            "kills" => $this->kills,
            "deaths" => $this->deaths,
        ];
    }

    public function getWins(): int
    {
        return $this->wins;
    }

    public function setWins(int $wins): void
    {
        if (!$this->updated) {
            $this->updated = true;
        }
        $this->wins = $wins;
    }

    public function getWinStreak(): int
    {
        return $this->winStreak;
    }

    public function setWinStreak(int $winStreak): void
    {
        if (!$this->updated) {
            $this->updated = true;
        }
        $this->winStreak = $winStreak;
    }

    public function getLosses(): int
    {
        return $this->losses;
    }

    public function setLosses(int $losses): void
    {
        if (!$this->updated) {
            $this->updated = true;
        }
        $this->losses = $losses;
    }
}
